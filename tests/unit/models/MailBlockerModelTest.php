<?php

namespace Winter\User\Tests\Unit\Models;

use Illuminate\Mail\Message;
use Mockery;
use Symfony\Component\Mime\Email;
use Winter\User\Models\MailBlocker;
use Winter\User\Models\User;
use Winter\User\Tests\UserPluginTestCase;

/**
 * @requires PHP >= 8.0
 */
class MailBlockerModelTest extends UserPluginTestCase
{
    public function testFilterMessage_to()
    {
        list(
            $userMock1,
            $userMock2,
            $userMock3
            ) = $this->getMockedUsers();

        $this->mockBlockers($userMock1, $userMock2);

        $message1 = $this->getMessage('test1', [$userMock1->email]);
        $message2 = $this->getMessage('test2', [$userMock2->email]);
        $message3 = $this->getMessage('test3', [$userMock3->email]);

        $message12 = $this->getMessage('test12', [$userMock1->email, $userMock2->email]);
        $message13 = $this->getMessage('test13', [$userMock1->email, $userMock3->email]);

        $filterMessage1 = MailBlocker::filterMessage(null, $message1);
        $filterMessage2 = MailBlocker::filterMessage(null, $message2);
        $filterMessage3 = MailBlocker::filterMessage(null, $message3);
        $filterMessage12 = MailBlocker::filterMessage(null, $message12);
        $filterMessage13 = MailBlocker::filterMessage(null, $message13);

        $this->assertFalse($filterMessage1);
        $this->assertEmpty($message1->getTo());
        $this->assertFalse($filterMessage2);
        $this->assertEmpty($message2->getTo());
        $this->assertNull($filterMessage3);
        $this->assertEquals($userMock3->email, $message3->getTo()[0]->getAddress());
        $this->assertFalse($filterMessage12);
        $this->assertEmpty($message12->getTo());
        $this->assertNull($filterMessage13);
        $this->assertEquals(1, count($message13->getTo()));
        $this->assertEquals($userMock3->email, $message13->getTo()[0]->getAddress());
    }

    public function testFilterMessage_ccAndBcc()
    {
        list(
            $userMock1,
            $userMock2,
            $userMock3,
            $userMock4
            ) = $this->getMockedUsers();

        $this->mockBlockers($userMock1, $userMock2);

        $messageTo0Cc12 = $this->getMessage(
            'test_to0_cc12',
            [],
            [$userMock1->email,$userMock2->email]
        );
        $messageTo3Cc14 = $this->getMessage(
            'test_to3_cc14',
            [$userMock3->email],
            [$userMock1->email,$userMock4->email]
        );
        $messageTo3Bcc14 = $this->getMessage(
            'test_to3_bcc14',
            [$userMock3->email],
            [],
            [$userMock1->email,$userMock4->email]
        );

        $filterMessageTo0Cc12 = MailBlocker::filterMessage(null, $messageTo0Cc12);
        $filterMessageTo3Cc14 = MailBlocker::filterMessage(null, $messageTo3Cc14);
        $filterMessageTo3Bcc14 = MailBlocker::filterMessage(null, $messageTo3Bcc14);

        $this->assertFalse($filterMessageTo0Cc12);
        $this->assertNull($filterMessageTo3Cc14);
        $this->assertNull($filterMessageTo3Bcc14);
        $this->assertEquals($userMock4->email, $messageTo3Cc14->getCc()[0]->getAddress());
        $this->assertEmpty($messageTo0Cc12->getCc());
        $this->assertEquals($userMock4->email, $messageTo3Bcc14->getBcc()[0]->getAddress());
    }

    public function testCheckForEmail()
    {
        list($userMock1, $userMock2, $userMock3) = $this->getMockedUsers();

        $this->mockBlockers($userMock1, $userMock2);

        $checkForEmail1 = MailBlocker::checkForEmail(null, $userMock1->email);
        $checkForEmail2 = MailBlocker::checkForEmail(null, $userMock2->email);
        $checkForEmail3 = MailBlocker::checkForEmail(null, $userMock3->email);
        $checkForEmail12 = MailBlocker::checkForEmail(null, [$userMock1->email, $userMock2->email]);
        $checkForEmail13 = MailBlocker::checkForEmail(null, [$userMock1->email, $userMock3->email]);

        $this->assertEquals([$userMock1->email], $checkForEmail1);
        $this->assertEquals([$userMock2->email], $checkForEmail2);
        $this->assertEmpty($checkForEmail3);
        $this->assertEquals([$userMock1->email, $userMock2->email], $checkForEmail12);
        $this->assertEquals([$userMock1->email], $checkForEmail13);
    }

    /**
     * Helper method that mocks 4 users, saves them and returns them
     * @return array
     */
    private static function getMockedUsers(): array
    {
        $userMock1 = Mockery::mock(User::class)->makePartial();
        $userMock2 = Mockery::mock(User::class)->makePartial();
        $userMock3 = Mockery::mock(User::class)->makePartial();
        $userMock4 = Mockery::mock(User::class)->makePartial();

        $userMock1->shouldReceive('isActivatedByUser')->andReturn(true);
        $userMock2->shouldReceive('isActivatedByUser')->andReturn(true);
        $userMock3->shouldReceive('isActivatedByUser')->andReturn(true);
        $userMock4->shouldReceive('isActivatedByUser')->andReturn(true);
        $userMock1->shouldReceive('flushEventListeners')->andReturnNull();
        $userMock2->shouldReceive('flushEventListeners')->andReturnNull();
        $userMock3->shouldReceive('flushEventListeners')->andReturnNull();
        $userMock4->shouldReceive('flushEventListeners')->andReturnNull();

        $userMock1->fill([
            'name' => 'test1',
            'email' => 'test1@website.tld',
            'password' => 'password',
        ]);
        $userMock2->fill([
            'name' => 'test2',
            'email' => 'test2@website.tld',
            'password' => 'password',
        ]);
        $userMock3->fill([
            'name' => 'test3',
            'email' => 'test3@website.tld',
            'password' => 'password',
        ]);
        $userMock4->fill([
            'name' => 'test3',
            'email' => 'test4@website.tld',
            'password' => 'password',
        ]);
        $userMock1->save();
        $userMock2->save();
        $userMock3->save();
        $userMock4->save();
        return array($userMock1, $userMock2, $userMock3, $userMock4);
    }

    /**
     * Helper method that mocks and saves 2 mail blockers for the two given users
     * @param mixed $userMock1
     * @param mixed $userMock2
     * @return void
     */
    private static function mockBlockers($userMock1, $userMock2): void
    {
        $mailBlockerMock1 = Mockery::mock(MailBlocker::class)->makePartial();
        $mailBlockerMock2 = Mockery::mock(MailBlocker::class)->makePartial();
        $mailBlockerMock1->shouldReceive('flushEventListeners')->andReturnNull();
        $mailBlockerMock2->shouldReceive('flushEventListeners')->andReturnNull();

        $mailBlockerMock1->email = $userMock1->email;
        $mailBlockerMock1->template = '*';
        $mailBlockerMock1->user_id = $userMock1->id;

        $mailBlockerMock2->email = $userMock2->email;
        $mailBlockerMock2->template = '*';
        $mailBlockerMock2->user_id = $userMock2->id;

        $mailBlockerMock1->save();
        $mailBlockerMock2->save();
    }

    /**
     * Helper to create and return a new Mail Message
     * @param string $subject
     * @param string[] $to
     * @param string[] $cc
     * @param string[] $bcc
     * @return Message
     */
    private static function getMessage(string $subject, array $to = [], array $cc = [], array $bcc = []): Message
    {
        $message = new Message(new Email());
        $message
            ->subject($subject)
            ->to($to)
            ->cc($cc)
            ->bcc($bcc)
        ;
        return $message;
    }
}
