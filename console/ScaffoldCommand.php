<?php namespace Winter\User\Console;

use Auth;
use Backend;
use Carbon\Carbon;
use File;
use Illuminate\Console\Command;
use System\Models\File as FileModel;
use Winter\User\Models\User;
use Winter\User\Models\UserGroup;

/**
 * Scaffolds Winter.User demo data for local development and testing.
 *
 * Creates ~30 users spanning every backend-visible state (activated,
 * not-activated, banned, guest, soft-deleted/trashed, superuser and a
 * long-name/long-email edge case) plus a handful of user groups with members
 * assigned, and attaches avatars to a few users — so every backend surface
 * (users list with its row-state styling + filters + bulk actions, the
 * user preview/create/update form tabs incl. the avatar upload and the
 * status hint banners, and the user groups list/form with its users_count
 * column) can be exercised. Mirrors the env-guarded, idempotent `scaffold:*`
 * pattern used elsewhere; scaffold rows are marked by the `@scaffold.example`
 * email domain (users) and a `scaffold-` code prefix (groups) so `--fresh`
 * can scope its cleanup to scaffold-created data only.
 */
class ScaffoldCommand extends Command
{
    protected $signature = 'scaffold:winter.user
        {--fresh : Delete existing scaffold data before recreating}';

    protected $description = 'Scaffold Winter.User demo data (varied users + user groups) for local development/testing.';

    /**
     * Recognisable marker used to keep the command idempotent and to scope
     * `--fresh` deletion to scaffold-created rows only.
     */
    const EMAIL_DOMAIN = '@scaffold.example';
    const GROUP_CODE_PREFIX = 'scaffold-';

    public function handle(): int
    {
        // Never inject demo content into a production install.
        if ($this->getLaravel()->environment('production')) {
            $this->error('scaffold:winter.user cannot run in the production environment.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->deleteExisting();
        }

        if (User::withTrashed()->where('email', 'like', '%' . self::EMAIL_DOMAIN)->exists()) {
            $this->warn('Winter.User scaffold data already exists. Use --fresh to recreate it.');

            return self::SUCCESS;
        }

        $groups = $this->createGroups();
        $this->info('Created ' . count($groups) . ' user groups.');

        $userCount = $this->createUsers($groups);
        $this->info("Created {$userCount} users.");

        $this->newLine();
        $this->line('Users:       ' . Backend::url('winter/user/users'));
        $this->line('User groups: ' . Backend::url('winter/user/usergroups'));
        $this->line('Settings:    ' . Backend::url('system/settings/update/winter/user/settings'));

        return self::SUCCESS;
    }

    /**
     * Remove previously scaffolded users (incl. soft-deleted rows, their
     * avatars, group pivots and throttle records) and scaffold groups.
     */
    protected function deleteExisting(): void
    {
        $users = User::withTrashed()
            ->where('email', 'like', '%' . self::EMAIL_DOMAIN)
            ->get();

        foreach ($users as $user) {
            if ($user->avatar) {
                $user->avatar->delete();
            }
            $user->groups()->detach();
            \Db::table('user_throttle')->where('user_id', $user->id)->delete();
            $user->forceDelete();
        }

        $groups = UserGroup::where('code', 'like', self::GROUP_CODE_PREFIX . '%')->get();
        foreach ($groups as $group) {
            $group->users()->detach();
            $group->delete();
        }

        if ($users->isNotEmpty() || $groups->isNotEmpty()) {
            $this->info("Removed {$users->count()} scaffold user(s) and {$groups->count()} group(s).");
        }
    }

    /**
     * Build a few user groups, including one with a deliberately long name to
     * test truncation in the list, the relation picker and the filter dropdown.
     */
    protected function createGroups(): array
    {
        $subscribers = $this->makeGroup('Subscribers', 'Users subscribed to the newsletter.');
        $premium = $this->makeGroup('Premium Members', 'Paying members with premium access.');
        $moderators = $this->makeGroup('Moderators', 'Trusted users who can moderate content.');
        $longName = $this->makeGroup(
            'A deliberately long user group name for testing truncation',
            'This group exists purely to test how long names render in the list, the users filter dropdown and the relation picker.'
        );

        return compact('subscribers', 'premium', 'moderators', 'longName');
    }

    protected function makeGroup(string $name, string $description): UserGroup
    {
        $code = self::GROUP_CODE_PREFIX . \Str::slug(\Str::limit($name, 48, ''));

        $group = new UserGroup();
        $group->name = $name;
        $group->code = $code;
        $group->description = $description;
        $group->save();

        return $group;
    }

    /**
     * Create a spread of users covering every backend-visible state, assign a
     * subset of them to groups, and attach avatars to a few.
     */
    protected function createUsers(array $groups): int
    {
        $groupList = array_values($groups);
        $avatarCount = 0;
        $count = 0;

        // 1. A superuser, activated, with an avatar.
        $u = $this->makeUser('Ada', 'Lovelace', 'ada.lovelace', [
            'activated'  => true,
            'superuser'  => true,
            'groups'     => [$groups['premium'], $groups['moderators']],
            'created_ip' => '203.0.113.10',
            'last_seen'  => Carbon::now()->subMinutes(2), // online
        ]);
        $this->attachAvatar($u, $avatarCount++);
        $count++;

        // 2. Activated regular user, with an avatar, member of a group.
        $u = $this->makeUser('Grace', 'Hopper', 'grace.hopper', [
            'activated'  => true,
            'groups'     => [$groups['subscribers']],
            'created_ip' => '198.51.100.23',
            'last_seen'  => Carbon::now()->subHours(3),
        ]);
        $this->attachAvatar($u, $avatarCount++);
        $count++;

        // 3. Activated user with an avatar and a very long name/email edge case.
        $u = $this->makeUser(
            'Maximiliana-Wilhelmina',
            'von Hindenburg-Schwarzenberg-Liechtenstein',
            'maximiliana.very.long.username.for.testing.overflow',
            [
                'activated' => true,
                'email'     => 'maximiliana.wilhelmina.von.hindenburg.schwarzenberg' . self::EMAIL_DOMAIN,
                'groups'    => [$groups['premium'], $groups['subscribers'], $groups['moderators'], $groups['longName']],
                'last_seen' => Carbon::now()->subDays(1),
            ]
        );
        $this->attachAvatar($u, $avatarCount++);
        $count++;

        // 4. Banned user (activated, then banned via throttle) -> "negative" row + banned hint.
        $u = $this->makeUser('Boris', 'Banned', 'boris.banned', [
            'activated'  => true,
            'groups'     => [$groups['subscribers']],
            'created_ip' => '192.0.2.44',
        ]);
        $u->ban();
        $count++;

        // 5. Not-activated user -> "disabled" row + activate hint.
        $this->makeUser('Nina', 'NotActivated', 'nina.notactivated', [
            'activated' => false,
            'groups'    => [$groups['subscribers']],
        ]);
        $count++;

        // 6. Guest user -> guest hint + convert-to-registered flow.
        $this->makeUser('Gary', 'Guest', 'gary.guest', [
            'activated' => false,
            'guest'     => true,
        ]);
        $count++;

        // 7. Trashed / soft-deleted user -> "strike" row + trashed hint.
        $u = $this->makeUser('Trish', 'Trashed', 'trish.trashed', [
            'activated' => true,
            'groups'    => [$groups['moderators']],
        ]);
        $u->delete();
        $count++;

        // 8. Banned AND not activated, to stack row styles.
        $u = $this->makeUser('Bella', 'BannedInactive', 'bella.bannedinactive', [
            'activated' => false,
        ]);
        $u->ban();
        $count++;

        // Filler users to populate the list (20/page) and the group memberships.
        $firstNames = [
            'Alan', 'Barbara', 'Charles', 'Diana', 'Edward', 'Fiona', 'George', 'Helen',
            'Ivan', 'Julia', 'Kevin', 'Laura', 'Marcus', 'Nadia', 'Oscar', 'Petra',
            'Quentin', 'Rosa', 'Steven', 'Tara', 'Umar', 'Vera',
        ];
        $lastNames = [
            'Turing', 'Liskov', 'Babbage', 'Ada', 'Dijkstra', 'Franklin', 'Boole', 'Keller',
            'Sutherland', 'Child', 'Mitnick', 'Perlman', 'Aurelius', 'Comaneci', 'Wilde', 'Kelly',
            'Tarantino', 'Parks', 'Spielberg', 'Reid', 'Khayyam', 'Rubin',
        ];

        for ($i = 0; $i < count($firstNames); $i++) {
            $first = $firstNames[$i];
            $last = $lastNames[$i];
            $username = strtolower($first . '.' . $last);

            // Vary states across the filler set.
            $activated = ($i % 4 !== 0);   // ~1 in 4 not activated
            $opts = [
                'activated'  => $activated,
                'created_at' => Carbon::now()->subDays(10 + $i),
            ];

            // Assign roughly two thirds to a rotating group.
            if ($i % 3 !== 0) {
                $opts['groups'] = [$groupList[$i % count($groupList)]];
            }

            // Give a couple of filler users last_seen values for the "online/offline" scoreboard.
            if ($i % 5 === 0) {
                $opts['last_seen'] = Carbon::now()->subMinutes($i + 1);
            }

            $u = $this->makeUser($first, $last, $username, $opts);

            // Sprinkle a few more avatars through the filler set.
            if ($i % 7 === 0) {
                $this->attachAvatar($u, $avatarCount++);
            }

            // Ban one filler user for extra "negative" rows.
            if ($i === 9) {
                $u->ban();
            }

            $count++;
        }

        return $count;
    }

    /**
     * Create a single user, honouring the provided state options and group
     * assignments. Uses forceSave so activation state can be set directly.
     */
    protected function makeUser(string $name, string $surname, string $handle, array $opts = []): User
    {
        $email = $opts['email'] ?? ($handle . self::EMAIL_DOMAIN);

        $user = new User();
        $user->name = $name;
        $user->surname = $surname;
        // Namespace the username with a scaffold marker so generated accounts
        // cannot collide with a pre-existing (non-scaffold) user's username,
        // which would otherwise abort the run (and survive --fresh cleanup).
        $user->username = 'scaffold-' . $handle;
        $user->email = $email;
        $user->password = 'scaffold-password';
        $user->password_confirmation = 'scaffold-password';
        $user->is_guest = $opts['guest'] ?? false;
        $user->is_superuser = $opts['superuser'] ?? false;

        if (!empty($opts['created_ip'])) {
            $user->created_ip_address = $opts['created_ip'];
            $user->last_ip_address = $opts['created_ip'];
        }

        // Suppress the invitation email during scaffolding.
        $user->send_invite = false;

        $user->save();

        // Apply activation state.
        if (!empty($opts['activated'])) {
            $user->is_activated = true;
            $user->activated_at = Carbon::now();
        } else {
            $user->is_activated = false;
        }

        if (!empty($opts['last_seen'])) {
            $user->last_seen = $opts['last_seen'];
        }

        if (!empty($opts['created_at'])) {
            $user->created_at = $opts['created_at'];
        }

        $user->forceSave();

        if (!empty($opts['groups'])) {
            $user->groups()->sync(collect($opts['groups'])->pluck('id')->all());
        }

        return $user;
    }

    /**
     * Attach one of the bundled source images as the user's avatar.
     */
    protected function attachAvatar(User $user, int $index): void
    {
        $sources = array_values(array_filter([
            base_path('themes/demo/assets/images/winter.png'),
            base_path('themes/demo/assets/images/theme-preview.png'),
            base_path('modules/backend/assets/images/wordmark.png'),
        ], fn ($path) => File::exists($path)));

        if (empty($sources)) {
            return;
        }

        $source = $sources[$index % count($sources)];

        $file = (new FileModel())->fromFile($source);
        $file->is_public = true;
        $file->save();

        $user->avatar()->add($file);
    }
}
