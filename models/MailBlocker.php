<?php

namespace Winter\User\Models;

use Model;
use Exception;
use System\Models\MailTemplate;

/**
 * Mail Blocker
 *
 * A utility model that allows a user to block specific
 * mail views/templates from being sent to their address.
 */
class MailBlocker extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'winter_user_mail_blockers';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'user' => User::class
    ];

    /**
     * @var array Templates names that cannot be blocked.
     */
    protected static $safeTemplates = [
        'winter.user::mail.restore'
    ];

    /**
     * Sets mail blocking preferences for a user. Eg:
     *
     * MailBlocker::setPreferences($user, [acme.blog::post.new_reply => 0])
     *
     * MailBlocker::setPreferences($user, [acme.blog::post.new_reply => 0], [fillable => [acme.blog::post.new_reply]])
     *
     * MailBlocker::setPreferences($user, [template_alias => 0], [aliases => [template_alias => acme.blog::post.new_reply]])
     *
     * Supported options:
     * - aliases: Alias definitions, with alias as key and template as value.
     * - fillable: An array of expected templates, undefined templates are ignored.
     * - verify: Only allow mail templates that are registered in the system.
     *
     * @param  array $templates Template name as key and boolean as value. If false, template is blocked.
     * @param  \Winter\User\Models\User $user
     * @param  array $options
     * @return void
     */
    public static function setPreferences($user, $templates, $options = [])
    {
        $templates = (array) $templates;

        if (!$user) {
            throw new Exception('A user must be provided for MailBlocker::setPreferences');
        }

        extract(array_merge([
            'aliases'  => [],
            'fillable' => [],
            'verify'   => false,
        ], $options));

        if ($aliases) {
            $fillable = array_merge($fillable, array_values($aliases));
            $templates = array_build($templates, function ($key, $value) use ($aliases) {
                return [array_get($aliases, $key, $key), $value];
            });
        }

        if ($fillable) {
            $templates = array_intersect_key($templates, array_flip($fillable));
        }

        if ($verify) {
            $existing = MailTemplate::listAllTemplates();
            $templates = array_intersect_key($templates, $existing);
        }

        $currentBlocks = array_flip(static::checkAllForUser($user));
        foreach ($templates as $template => $value) {
            // User wants to receive mail and is blocking
            if ($value && isset($currentBlocks[$template])) {
                static::removeBlock($template, $user);
            }
            // User does not want to receive mail and not blocking
            elseif (!$value && !isset($currentBlocks[$template])) {
                static::addBlock($template, $user);
            }
        }
    }

    /**
     * Adds a block for a user and a mail view/template code.
     * @param string                   $template
     * @param \Winter\User\Models\User $user
     * @return bool
     */
    public static function addBlock($template, $user)
    {
        $blocker = static::where([
            'template' => $template,
            'user_id'  => $user->id
        ])->first();

        if ($blocker && $blocker->email == $user->email) {
            return false;
        }

        if (!$blocker) {
            $blocker = new static;
            $blocker->template = $template;
            $blocker->user_id = $user->id;
        }

        $blocker->email = $user->email;
        $blocker->save();

        return true;
    }

    /**
     * Removes a block for a user and a mail view/template code.
     * @param string                   $template
     * @param \Winter\User\Models\User $user
     * @return bool
     */
    public static function removeBlock($template, $user)
    {
        $blocker = static::where([
            'template' => $template,
            'user_id'  => $user->id
        ])->orWhere(function ($query) use ($template, $user) {
            $query->where([
                'template' => $template,
                'email'    => $user->email
            ]);
        })->get();

        if (!$blocker->count()) {
            return false;
        }

        $blocker->each(function ($block) {
            $block->delete();
        });

        return true;
    }

    /**
     * Blocks all mail messages for a user.
     * @param \Winter\User\Models\User $user
     * @return bool
     */
    public static function blockAll($user)
    {
        return static::addBlock('*', $user);
    }

    /**
     * Removes block on all mail messages for a user.
     * @param \Winter\User\Models\User $user
     * @return bool
     */
    public static function unblockAll($user)
    {
        return static::removeBlock('*', $user);
    }

    /**
     * Checks if a user is blocking all templates.
     * @param \Winter\User\Models\User $user
     * @return bool
     */
    public static function isBlockAll($user)
    {
        return count(static::checkForEmail('*', $user->email)) > 0;
    }

    /**
     * Updates mail blockers for a user if they change their email address
     * @param  Model $user
     * @return mixed
     */
    public static function syncUser($user)
    {
        return static::where('user_id', $user->id)->update(['email' => $user->email]);
    }

    /**
     * Returns a list of mail templates blocked by the user.
     * @param  Model $user
     * @return array
     */
    public static function checkAllForUser($user)
    {
        return static::where('user_id', $user->id)->lists('template');
    }

    /**
     * Checks if an email address has blocked a given template,
     * returns an array of blocked emails.
     * @param ?string $template
     * @param string|string[] $email
     * @return array
     */
    public static function checkForEmail(?string $template, $email): array
    {
        if (in_array($template, static::$safeTemplates)) {
            return [];
        }

        if (empty($email)) {
            return [];
        }

        $emails = is_array($email) ? $email : [$email];

        return static::where(
            function ($query) use ($template) {
                $query
                    ->where('template', $template)
                    ->orWhere('template', '*');
            }
        )->whereIn('email', $emails)->lists('email');
    }

    /**
     * Filters a \Illuminate\Mail\Message and removes blocked recipients.
     * If no recipients remain, false is returned. Returns null if mailing
     * should proceed.
     * @param  string $template
     * @param  \Illuminate\Mail\Message $message
     * @return bool|null
     */
    public static function filterMessage($template, $message): ?bool
    {
        $recipients = array_map(function ($address) {
            return $address->getAddress();
        }, $message->getTo());
        $ccRecipients = array_map(function ($address) {
            return $address->getAddress();
        }, $message->getCc());
        $bccRecipients = array_map(function ($address) {
            return $address->getAddress();
        }, $message->getBcc());

        $allRecipients = array_merge($recipients, $ccRecipients, $bccRecipients);
        $allRecipientsNoDuplicates = array_combine($allRecipients, $allRecipients);

        $blockedAddresses = static::checkForEmail($template, $allRecipientsNoDuplicates);

        if (!count($blockedAddresses)) {
            return null;
        }

        $recipients = array_filter($recipients, function ($address) use (&$blockedAddresses) {
            return !in_array($address, $blockedAddresses);
        });
        $ccRecipients = array_filter($ccRecipients, function ($address) use (&$blockedAddresses) {
            return !in_array($address, $blockedAddresses);
        });
        $bccRecipients = array_filter($bccRecipients, function ($address) use (&$blockedAddresses) {
            return !in_array($address, $blockedAddresses);
        });

        $message->to($recipients, null, true);
        $message->cc($ccRecipients, null, true);
        $message->bcc($bccRecipients, null, true);
        return (count($recipients) + count($ccRecipients) + count($bccRecipients)) ? null : false;
    }
}
