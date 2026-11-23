<?php

namespace Winter\User\Tests\Unit\Components;

use Auth;
use Flash;
use Lang;
use Winter\User\Components\Account;
use Winter\User\Models\User;
use Winter\User\Tests\UserPluginTestCase;

class AccountTest extends UserPluginTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Flash::purge();
    }

    public function testActivationLinkActivatesTheUser()
    {
        $user = $this->createUser();

        (new Account(null, []))->onActivate($user->id . '!' . $user->getActivationCode());

        $this->assertTrue(User::find($user->id)->is_activated);
        $this->assertTrue(Auth::check());
        $this->assertEquals([Lang::get('winter.user::lang.account.success_activation')], Flash::get('success'));
    }

    public function testActivationLinkOpenedAgainIsNoError()
    {
        $user = $this->createUser();
        $link = $user->id . '!' . $user->getActivationCode();
        $user->attemptActivation(explode('!', $link)[1]);

        (new Account(null, []))->onActivate($link);

        $this->assertEquals([], Flash::get('error'));
        $this->assertEquals([Lang::get('winter.user::lang.account.already_active')], Flash::get('success'));
        $this->assertFalse(Auth::check(), 'a used link must not sign anyone in');
    }

    public function testWrongActivationCodeIsReported()
    {
        $user = $this->createUser();
        $user->getActivationCode();

        (new Account(null, []))->onActivate($user->id . '!wrong');

        $this->assertFalse(User::find($user->id)->is_activated);
        $this->assertEquals([Lang::get('winter.user::lang.account.invalid_activation_code')], Flash::get('error'));
    }

    public function testOtherValueInTheCodeParameterIsNoActivation()
    {
        $account = $this->getMockBuilder(Account::class)
            ->setConstructorArgs([null, []])
            ->onlyMethods(['activationCode', 'onActivate', 'prepareVars'])
            ->getMock();
        $account->method('activationCode')->willReturn('settings');
        $account->expects($this->never())->method('onActivate');

        $account->onRun();
    }

    public function testActivationCodeInTheCodeParameterIsActivated()
    {
        $account = $this->getMockBuilder(Account::class)
            ->setConstructorArgs([null, []])
            ->onlyMethods(['activationCode', 'onActivate', 'prepareVars'])
            ->getMock();
        $account->method('activationCode')->willReturn('1!abc');
        $account->expects($this->once())->method('onActivate')->with('1!abc');

        $account->onRun();
    }

    protected function createUser(): User
    {
        return Auth::register([
            'name' => 'Some User',
            'email' => 'some@website.tld',
            'password' => 'changeme',
            'password_confirmation' => 'changeme',
        ], false, false);
    }
}
