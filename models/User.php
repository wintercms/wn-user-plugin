<?php namespace Winter\User\Models;

use Str;
use Auth;
use Mail;
use Event;
use Config;
use BackendAuth;
use Carbon\Carbon;
use Winter\Storm\Auth\Models\User as UserBase;
use Winter\User\Models\Settings as UserSettings;
use Winter\Storm\Auth\AuthException;

class User extends UserBase
{
    use \Winter\Storm\Database\Traits\SoftDelete;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'users';

    /**
     * Validation rules
     */
    public $rules = [
        'email'    => 'required|between:6,255|email|unique:users',
        'avatar'   => 'nullable|image|max:4000',
        'username' => 'required|between:2,255|unique:users',
        'password' => 'required:create|between:8,255|confirmed',
        'password_confirmation' => 'required_with:password|between:8,255',
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'groups' => [UserGroup::class, 'table' => 'users_groups']
    ];

    public $attachOne = [
        'avatar' => \System\Models\File::class
    ];

    /**
     * @var array The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'surname',
        'login',
        'username',
        'email',
        'password',
        'password_confirmation',
        'created_ip_address',
        'last_ip_address'
    ];

    /**
     * Reset guarded fields, because we use $fillable instead.
     * @var array The attributes that aren't mass assignable.
     */
    protected $guarded = ['*'];


    /**
     * Purge attributes from data set.
     */
    protected $purgeable = ['password_confirmation', 'send_invite'];

    protected $dates = [
        'last_seen',
        'deleted_at',
        'created_at',
        'updated_at',
        'activated_at',
        'last_login'
    ];

    public static $loginAttribute = null;

    /**
     * Sends the confirmation email to a user, after activating.
     * @param  string $code
     * @return bool
     */
    public function attemptActivation($code)
    {
        if ($this->trashed()) {
            if ($code === $this->activation_code) {
                $this->restore();
            } else {
                return false;
            }
        } else {
            $result = parent::attemptActivation($code);

            if ($result === false) {
                return false;
            }
        }

        Event::fire('winter.user.activate', [$this]);

        return true;
    }

    /**
     * Attempts to reset a user's password by matching the reset code generated with the user's.
     *
     * If user activation is enabled, the user will be activated as well.
     *
     * @param string $resetCode
     * @param string $newPassword
     * @return bool
     */
    public function attemptResetPassword($resetCode, $newPassword)
    {
        if (!parent::attemptResetPassword($resetCode, $newPassword)) {
            return false;
        }

        if ($this->isActivatedByUser()) {
            $this->activation_code = null;
            $this->is_activated = true;
            $this->activated_at = $this->freshTimestamp();
            $this->forceSave();
        }

        return true;
    }

    /**
     * Converts a guest user to a registered one and sends an invitation notification.
     * @return void
     */
    public function convertToRegistered($sendNotification = true)
    {
        // Already a registered user
        if (!$this->is_guest) {
            return;
        }

        if ($sendNotification) {
            $this->generatePassword();
        }

        $this->is_guest = false;
        $this->save();

        if ($sendNotification) {
            $this->sendInvitation();
        }
    }

    //
    // Constructors
    //

    /**
     * Looks up a user by their email address.
     * @return self
     */
    public static function findByEmail($email)
    {
        if (!$email) {
            return;
        }

        return self::where('email', $email)->first();
    }

    //
    // Getters
    //

    /**
     * Gets a code for when the user is persisted to a cookie or session which identifies the user.
     * @return string
     */
    public function getPersistCode()
    {
        $block = UserSettings::get('block_persistence', false);

        if ($block || !$this->persist_code) {
            return parent::getPersistCode();
        }

        return $this->persist_code;
    }

    /**
     * Returns the public image file path to this user's avatar.
     */
    public function getAvatarThumb($size = 25, $options = null)
    {
        if (is_string($options)) {
            $options = ['default' => $options];
        }
        elseif (!is_array($options)) {
            $options = [];
        }

        // Default is "mm" (Mystery man)
        $default = array_get($options, 'default', 'mm');

        if ($this->avatar) {
            return $this->avatar->getThumb($size, $size, $options);
        }
        else {
            return '//www.gravatar.com/avatar/'.
                md5(strtolower(trim($this->email))).
                '?s='.$size.
                '&d='.urlencode($default);
        }
    }

    /**
     * Returns the name for the user's login.
     * @return string
     */
    public function getLoginName()
    {
        if (static::$loginAttribute !== null) {
            return static::$loginAttribute;
        }

        return static::$loginAttribute = UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    /**
     * Returns the minimum length for a new password from settings.
     * @return int
     */
    public static function getMinPasswordLength()
    {
        return Config::get('winter.user::minPasswordLength', 8);
    }

    //
    // Scopes
    //

    public function scopeIsActivated($query)
    {
        return $query->where('is_activated', 1);
    }

    public function scopeFilterByGroup($query, $filter)
    {
        return $query->whereHas('groups', function($group) use ($filter) {
            $group->whereIn('id', $filter);
        });
    }

    //
    // Events
    //

    /**
     * Before validation event
     * @return void
     */
    public function beforeValidate()
    {
        /*
         * Guests are special
         */
        if ($this->is_guest && !$this->password) {
            $this->generatePassword();
        }

        /*
         * When the username is not used, the email is substituted.
         */
        if (
            (!$this->username) ||
            ($this->isDirty('email') && $this->getOriginal('email') == $this->username)
        ) {
            $this->username = $this->email;
        }

        /*
         * Apply Password Length Settings
         */
        $minPasswordLength = static::getMinPasswordLength();
        $this->rules['password'] = "required:create|between:$minPasswordLength,255|confirmed";
        $this->rules['password_confirmation'] = "required_with:password|between:$minPasswordLength,255";
    }

    /**
     * After create event
     * @return void
     */
    public function afterCreate()
    {
        $this->restorePurgedValues();

        if ($this->send_invite) {
            $this->sendInvitation();
        }
    }

    /**
     * Before login event
     * @return void
     */
    public function beforeLogin()
    {
        if ($this->is_guest) {
            $login = $this->getLogin();
            throw new AuthException(sprintf(
                'Cannot login user "%s" as they are not registered.', $login
            ));
        }

        parent::beforeLogin();
    }

    /**
     * After login event
     * @return void
     */
    public function afterLogin()
    {
        $this->last_login = $this->freshTimestamp();

        if ($this->trashed()) {
            $this->restore();

            Mail::sendTo($this, 'winter.user::mail.reactivate', [
                'name' => $this->name
            ]);

            Event::fire('winter.user.reactivate', [$this]);
        }
        else {
            parent::afterLogin();
        }

        Event::fire('winter.user.login', [$this]);
    }

    /**
     * After delete event
     * @return void
     */
    public function afterDelete()
    {
        if ($this->isSoftDelete()) {
            Event::fire('winter.user.deactivate', [$this]);
            return;
        }

        $this->avatar && $this->avatar->delete();

        parent::afterDelete();
    }

    //
    // Banning
    //

    /**
     * Ban this user, preventing them from signing in.
     * @return void
     */
    public function ban()
    {
        Auth::findThrottleByUserId($this->id)->ban();
    }

    /**
     * Remove the ban on this user.
     * @return void
     */
    public function unban()
    {
        Auth::findThrottleByUserId($this->id)->unban();
    }

    /**
     * Check if the user is banned.
     * @return bool
     */
    public function isBanned()
    {
        $throttle = Auth::createThrottleModel()->where('user_id', $this->id)->first();
        return $throttle ? $throttle->is_banned : false;
    }

    //
    // Suspending
    //

    /**
     * Check if the user is suspended.
     * @return bool
     */
    public function isSuspended()
    {
        return Auth::findThrottleByUserId($this->id)->checkSuspended();
    }

    /**
     * Remove the suspension on this user.
     * @return void
     */
    public function unsuspend()
    {
        Auth::findThrottleByUserId($this->id)->unsuspend();
    }

    //
    // IP Recording and Throttle
    //

    /**
     * Records the last_ip_address to reflect the last known IP for this user.
     * @param string|null $ipAddress
     * @return void
     */
    public function touchIpAddress($ipAddress)
    {
        $this
            ->newQuery()
            ->where('id', $this->id)
            ->update(['last_ip_address' => $ipAddress])
        ;
    }

    /**
     * Returns true if IP address is throttled and cannot register
     * again. Maximum 3 registrations every 60 minutes.
     * @param string|null $ipAddress
     * @return bool
     */
    public static function isRegisterThrottled($ipAddress)
    {
        if (!$ipAddress) {
            return false;
        }

        $timeLimit = Carbon::now()->subMinutes(60);
        $count = static::make()
            ->where('created_ip_address', $ipAddress)
            ->where('created_at', '>', $timeLimit)
            ->count()
        ;

        return $count > 2;
    }

    //
    // Last Seen
    //

    /**
     * Checks if the user has been seen in the last 5 minutes, and if not,
     * updates the last_seen timestamp to reflect their online status.
     * @return void
     */
    public function touchLastSeen()
    {
        if ($this->isOnline()) {
            return;
        }

        $oldTimestamps = $this->timestamps;
        $this->timestamps = false;

        $this
            ->newQuery()
            ->where('id', $this->id)
            ->update(['last_seen' => $this->freshTimestamp()])
        ;

        $this->last_seen = $this->freshTimestamp();
        $this->timestamps = $oldTimestamps;
    }

    /**
     * Returns true if the user has been active within the last 5 minutes.
     * @return bool
     */
    public function isOnline()
    {
        return $this->getLastSeen() > $this->freshTimestamp()->subMinutes(5);
    }

    /**
     * Returns the date this user was last seen.
     * @return Carbon\Carbon
     */
    public function getLastSeen()
    {
        return $this->last_seen ?: $this->created_at;
    }

    //
    // Utils
    //

    /**
     * Returns the variables available when sending a user notification.
     * @return array
     */
    public function getNotificationVars()
    {
        $vars = [
            'name'     => $this->name,
            'email'    => $this->email,
            'username' => $this->username,
            'login'    => $this->getLogin(),
            'password' => $this->getOriginalHashValue('password')
        ];

        /*
         * Extensibility
         */
        $results = Event::fire('winter.user.getNotificationVars', [$this]);
        if ($results && is_array($results)) {
            $tempResults = [];
            foreach ($results as $result) {
                if ($result && is_array($result)) {
                    $tempResults = array_merge($tempResults, $result);
                }
            }
            $vars = $tempResults + $vars;
        }

        return $vars;
    }

    /**
     * Sends an invitation to the user using template "winter.user::mail.invite".
     * @return void
     */
    protected function sendInvitation()
    {
        Mail::sendTo($this, 'winter.user::mail.invite', $this->getNotificationVars());
    }

    /**
     * Assigns this user with a random password.
     * @return void
     */
    protected function generatePassword()
    {
        $this->password = $this->password_confirmation = Str::random(static::getMinPasswordLength());
    }

    //
    // Impersonation
    //

    /**
     * Check if this user can be impersonated by the provided impersonator
     * Only backend users with the `winter.users.impersonate_user` permission are allowed to impersonate
     * users.
     *
     * @param \Winter\Storm\Auth\Models\User|false $impersonator The user attempting to impersonate this user, false when not available
     * @return boolean
     */
    public function canBeImpersonated($impersonator = false)
    {
        $user = BackendAuth::getUser();
        if (!$user || !$user->hasAccess('winter.users.impersonate_user')) {
            return false;
        }

        return true;
    }

    /**
     * Determines if activation is done by the user.
     */
    public function isActivatedByUser(): bool
    {
        return (UserSettings::get('activate_mode') === UserSettings::ACTIVATE_USER);
    }
}
