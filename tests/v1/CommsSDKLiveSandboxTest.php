<?php

namespace PahappaLimited\CommsSDK\Tests\v1;

use PahappaLimited\CommsSDK\v1\CommsSDK;
use PHPUnit\Framework\TestCase;

/**
 * Live smoke test against the real sandbox API. Skipped entirely unless
 * COMMS_SANDBOX_USERNAME and COMMS_SANDBOX_API_KEY are set in the environment,
 * so it never fails CI or a dev machine that doesn't have credentials, and no
 * credential is ever hardcoded here.
 */
class CommsSDKLiveSandboxTest extends TestCase
{
    protected function setUp(): void
    {
        CommsSDK::setHttpClient(null);
    }

    public function testSendSmsSucceedsAgainstSandbox()
    {
        $username = getenv('COMMS_SANDBOX_USERNAME');
        $apiKey = getenv('COMMS_SANDBOX_API_KEY');

        if (!$username || !$apiKey) {
            $this->markTestSkipped('COMMS_SANDBOX_USERNAME / COMMS_SANDBOX_API_KEY not set; skipping live sandbox test.');
        }

        CommsSDK::useSandBox();
        $sdk = CommsSDK::authenticate($username, $apiKey);
        $this->assertTrue($sdk->isAuthenticated(), 'authentication against the sandbox failed');

        $result = $sdk->sendSMS('256700000000', 'Test message from PHP SDK live sandbox test');
        $this->assertTrue($result, 'sending an SMS via the sandbox failed');
    }
}
