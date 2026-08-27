<?php namespace Winter\User\Tests\Feature\Console;

use Artisan;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Winter\User\Console\ScaffoldCommand;
use Winter\User\Models\User;
use Winter\User\Models\UserGroup;
use Winter\User\Tests\UserPluginTestCase;

class ScaffoldCommandTest extends UserPluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Plugin console commands are registered via ConsoleApplication::starting, which has
        // already fired by the time the test harness boots the plugin — so the command isn't
        // resolvable through Artisan here. Register it directly with the kernel for the test.
        $this->app->make(ConsoleKernel::class)->registerCommand(new ScaffoldCommand());
    }

    protected function scaffoldUserCount(): int
    {
        return User::withTrashed()->where('email', 'like', '%' . ScaffoldCommand::EMAIL_DOMAIN)->count();
    }

    protected function scaffoldGroupCount(): int
    {
        return UserGroup::where('code', 'like', ScaffoldCommand::GROUP_CODE_PREFIX . '%')->count();
    }

    public function testCreatesDemoUsersAndGroups()
    {
        $this->assertSame(0, $this->scaffoldUserCount(), 'No scaffold users should exist beforehand.');

        $exitCode = Artisan::call('scaffold:winter.user');

        $this->assertSame(0, $exitCode);
        $this->assertSame(4, $this->scaffoldGroupCount());
        $this->assertSame(30, $this->scaffoldUserCount());

        // Should span backend-visible states: at least one superuser and one trashed user.
        $this->assertTrue(
            User::where('email', 'like', '%' . ScaffoldCommand::EMAIL_DOMAIN)->where('is_superuser', true)->exists(),
            'The scaffold should include a superuser.'
        );
        $this->assertTrue(
            User::onlyTrashed()->where('email', 'like', '%' . ScaffoldCommand::EMAIL_DOMAIN)->exists(),
            'The scaffold should include a soft-deleted user.'
        );
    }

    public function testIsIdempotentWithoutFresh()
    {
        Artisan::call('scaffold:winter.user');

        $exitCode = Artisan::call('scaffold:winter.user');

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('already exists', Artisan::output());
        $this->assertSame(4, $this->scaffoldGroupCount(), 'A second run must not duplicate groups.');
        $this->assertSame(30, $this->scaffoldUserCount(), 'A second run must not duplicate users.');
    }

    public function testFreshRecreatesTheData()
    {
        Artisan::call('scaffold:winter.user');
        $firstIds = User::withTrashed()->where('email', 'like', '%' . ScaffoldCommand::EMAIL_DOMAIN)->pluck('id')->all();

        $exitCode = Artisan::call('scaffold:winter.user', ['--fresh' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(4, $this->scaffoldGroupCount());
        $this->assertSame(30, $this->scaffoldUserCount());

        $newIds = User::withTrashed()->where('email', 'like', '%' . ScaffoldCommand::EMAIL_DOMAIN)->pluck('id')->all();
        $this->assertEmpty(array_intersect($firstIds, $newIds), '--fresh should delete and recreate the users.');
    }

    public function testRefusesToRunInProduction()
    {
        $this->app['env'] = 'production';

        $exitCode = Artisan::call('scaffold:winter.user');

        $this->assertSame(1, $exitCode);
        $this->assertSame(0, $this->scaffoldUserCount(), 'Nothing should be created in production.');

        $this->app['env'] = 'testing';
    }
}
