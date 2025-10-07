<?php

namespace PahappaLimited\CommsSDK\Tests\v1;

use PahappaLimited\CommsSDK\v1\CommsSDK;
use PahappaLimited\CommsSDK\v1\models\MessagePriority;
use PHPUnit\Framework\TestCase;

class CommsSDKTest extends TestCase
{
    private $sdk;

    protected function setUp(): void
    {
        CommsSDK::useSandBox();
        $this->sdk = CommsSDK::authenticate('agabu-idaniel', 'dcfa634d7936ec699a3b26f6cd924801b09b285a31949f99');
    }

    public function testSendSMSToSingleNumber()
    {
        $this->assertTrue($this->sdk->sendSMS('+256772123456', 'Test message'));
    }

    public function testSendSMSToMultipleNumbers()
    {
        $numbers = ['+256772123456', '0772123457'];
        $this->assertTrue($this->sdk->sendSMS($numbers, 'Test message'));
    }

    public function testSendSMSWithShortNumberLength()
    {
        $this->assertFalse($this->sdk->sendSMS('123', 'Test message'));
    }

    public function testSendSMSWithCustomMessagePriority()
    {
        $this->assertTrue($this->sdk->sendSMS('+256772123456', 'Test message', null, MessagePriority::LOW));
    }

    public function testSendSMSWithInvalidCredentials()
    {
        $sdk = CommsSDK::authenticate('invalid_user', 'invalid_password');
        $this->assertFalse($sdk->sendSMS('+256772123456', 'Test message'));
    }

    public function testCheckBalanceAfterSendingSMS()
    {
        $balanceBefore = $this->sdk->getBalance();
        $this->sdk->sendSMS('+256772123456', 'Test message');
        $balanceAfter = $this->sdk->getBalance();
        $this->assertLessThan($balanceBefore, $balanceAfter);
    }
}
