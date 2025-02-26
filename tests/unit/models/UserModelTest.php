<?php

namespace Winter\User\Tests\Unit\Models;

use Mockery;
use Winter\User\Models\User;
use Winter\User\Tests\UserPluginTestCase;

class UserModelTest extends UserPluginTestCase
{
    public function testCanActivateUserOnPasswordResetIfUserActivationEnabled()
    {
        $userMock = Mockery::mock(User::class)->makePartial();
        $userMock->shouldReceive('isActivatedByUser')->andReturn(true);
        $userMock->shouldReceive('flushEventListeners')->andReturnNull();

        $userMock->fill([
            'name' => 'Some User',
            'email' => 'some@website.tld',
            'password' => 'changeme',
        ]);
        $userMock->reset_password_code = '12345abcde';
        $userMock->save();

        // Attempt a password reset
        $this->assertTrue($userMock->attemptResetPassword('12345abcde', 'newpassword'));
        $this->assertTrue($userMock->is_activated);
    }

    public function testWontActivateUserOnPasswordResetIfUserActivationNotEnabled()
    {
        $userMock = Mockery::mock(User::class)->makePartial();
        $userMock->shouldReceive('isActivatedByUser')->andReturn(false);
        $userMock->shouldReceive('flushEventListeners')->andReturnNull();

        $userMock->fill([
            'name' => 'Some User',
            'email' => 'some@website.tld',
            'password' => 'changeme',
        ]);
        $userMock->reset_password_code = '12345abcde';
        $userMock->save();

        // Attempt a password reset
        $this->assertTrue($userMock->attemptResetPassword('12345abcde', 'newpassword'));
        $this->assertFalse($userMock->is_activated);
    }
}
