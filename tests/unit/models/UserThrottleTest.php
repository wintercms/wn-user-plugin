<?php

namespace Winter\User\Tests\Unit\Models;

use Auth;
use Winter\User\Models\Throttle;
use Winter\User\Models\User;
use Winter\User\Tests\UserPluginTestCase;

class UserThrottleTest extends UserPluginTestCase
{
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Some User',
            'email' => 'some@website.tld',
            'password' => 'changeme',
            'password_confirmation' => 'changeme',
        ]);
    }

    public function testIsSuspendedChecksEveryThrottleRecord()
    {
        $this->makeThrottle('192.0.2.1');
        $this->makeThrottle('192.0.2.2', ['attempts' => 5, 'is_suspended' => true, 'suspended_at' => now()]);

        $this->assertTrue($this->user->isSuspended());
    }

    public function testIsSuspendedIgnoresAnExpiredSuspension()
    {
        $this->makeThrottle('192.0.2.1', ['attempts' => 5, 'is_suspended' => true, 'suspended_at' => now()->subHour()]);

        $this->assertFalse($this->user->isSuspended());
    }

    public function testUnsuspendLiftsEveryThrottleRecord()
    {
        $this->makeThrottle('192.0.2.1');
        $this->makeThrottle('192.0.2.2', ['attempts' => 5, 'is_suspended' => true, 'suspended_at' => now()]);

        $this->user->unsuspend();

        $this->assertFalse($this->user->isSuspended());
        $this->assertFalse(Auth::findThrottleByUserId($this->user->id, '192.0.2.2')->checkSuspended());
    }

    public function testIsBannedChecksEveryThrottleRecord()
    {
        $this->makeThrottle('192.0.2.1');
        $this->makeThrottle('192.0.2.2', ['is_banned' => true, 'banned_at' => now()]);

        $this->assertTrue($this->user->isBanned());
    }

    public function testBanCoversEveryIpAddress()
    {
        $this->makeThrottle('192.0.2.1');
        $this->makeThrottle('192.0.2.2');

        $this->user->ban();

        $this->assertTrue($this->user->isBanned());
        $this->assertTrue(Auth::findThrottleByUserId($this->user->id, '192.0.2.1')->is_banned);
        $this->assertTrue(Auth::findThrottleByUserId($this->user->id, '192.0.2.2')->is_banned);
        $this->assertTrue(Auth::findThrottleByUserId($this->user->id, '192.0.2.3')->is_banned);
    }

    public function testUnbanLiftsEveryThrottleRecord()
    {
        $this->makeThrottle('192.0.2.1');
        $this->makeThrottle('192.0.2.2');
        $this->user->ban();

        $this->user->unban();

        $this->assertFalse($this->user->isBanned());
        $this->assertSame(0, Throttle::where('user_id', $this->user->id)->where('is_banned', true)->count());
    }

    protected function makeThrottle(string $ipAddress, array $attributes = []): Throttle
    {
        $throttle = new Throttle;
        $throttle->forceFill(array_merge(['user_id' => $this->user->id, 'ip_address' => $ipAddress], $attributes));
        $throttle->save();

        return $throttle;
    }
}
