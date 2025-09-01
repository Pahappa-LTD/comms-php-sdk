<?php

namespace PahappaLimited\EgoSmsSdk\Tests\v1;

use PahappaLimited\EgoSmsSdk\v1\EgoSmsSDK;
use PahappaLimited\EgoSmsSdk\v1\models\MessagePriority;
use PHPUnit\Framework\TestCase;

class EgoSmsSDKTest extends TestCase
{
    private $sdk;

    protected function setUp(): void
    {
        EgoSmsSDK::useSandBox();
        $this->sdk = EgoSmsSDK::authenticate('aganisandbox', 'SandBox');
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
        $sdk = EgoSmsSDK::authenticate('invalid_user', 'invalid_password');
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
