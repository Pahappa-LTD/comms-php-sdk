<?php

namespace PahappaLimited\CommsSDK\Tests\v1;

use PahappaLimited\CommsSDK\v1\CommsSDK;
use PHPUnit\Framework\TestCase;

/**
 * Live smoke tests against the real sandbox API — never live production.
 * The gated tests are skipped entirely unless COMMS_SANDBOX_USERNAME and
 * COMMS_SANDBOX_API_KEY are set in the environment, so they never fail CI
 * or a dev machine without credentials, and no real credential is ever
 * hardcoded here. The wrong-credentials test always runs (costs nothing).
 */
class CommsSDKLiveSandboxTest extends TestCase
{
    protected function setUp(): void
    {
        CommsSDK::setHttpClient(null);
    }

    private function sandboxCredentials(): ?array
    {
        $username = getenv('COMMS_SANDBOX_USERNAME');
        $apiKey = getenv('COMMS_SANDBOX_API_KEY');

        if (!$username || !$apiKey) {
            return null;
        }

        return [$username, $apiKey];
    }

    public function testWrongCredentialsFailToAuthenticateAgainstSandbox()
    {
        CommsSDK::useSandBox();
        $sdk = CommsSDK::authenticate('invalid-user', 'invalid-key-00000000000000000000000000000000');
        $this->assertFalse($sdk->isAuthenticated(), 'authentication with an invalid credential should fail');
    }

    public function testSendSmsSucceedsAgainstSandbox()
    {
        $credentials = $this->sandboxCredentials();
        if ($credentials === null) {
            $this->markTestSkipped('COMMS_SANDBOX_USERNAME / COMMS_SANDBOX_API_KEY not set; skipping live sandbox test.');
        }
        [$username, $apiKey] = $credentials;

        CommsSDK::useSandBox();
        $sdk = CommsSDK::authenticate($username, $apiKey);
        $this->assertTrue($sdk->isAuthenticated(), 'authentication against the sandbox failed');

        $result = $sdk->sendSMS('256700000000', 'Test message from PHP SDK live sandbox test');
        $this->assertTrue($result, 'sending an SMS via the sandbox failed');
    }

    public function testSendSmsToMultipleNumbersSucceedsAgainstSandbox()
    {
        $credentials = $this->sandboxCredentials();
        if ($credentials === null) {
            $this->markTestSkipped('COMMS_SANDBOX_USERNAME / COMMS_SANDBOX_API_KEY not set; skipping live sandbox test.');
        }
        [$username, $apiKey] = $credentials;

        CommsSDK::useSandBox();
        $sdk = CommsSDK::authenticate($username, $apiKey);
        $this->assertTrue($sdk->isAuthenticated(), 'authentication against the sandbox failed');

        $numbers = ['256700000000', '256700000001', '256700000002'];
        $result = $sdk->sendSMS($numbers, 'Test multi-number message from PHP SDK live sandbox test');
        $this->assertTrue($result, 'sending an SMS to multiple numbers via the sandbox failed');
    }

    public function testSendSmsRejectsMoreThan1000NumbersAgainstSandbox()
    {
        $credentials = $this->sandboxCredentials();
        if ($credentials === null) {
            $this->markTestSkipped('COMMS_SANDBOX_USERNAME / COMMS_SANDBOX_API_KEY not set; skipping live sandbox test.');
        }
        [$username, $apiKey] = $credentials;

        CommsSDK::useSandBox();
        $sdk = CommsSDK::authenticate($username, $apiKey);
        $this->assertTrue($sdk->isAuthenticated(), 'authentication against the sandbox failed');

        $numbers = [];
        for ($i = 0; $i <= 1000; $i++) {
            $numbers[] = sprintf('256700%06d', $i);
        }
        $this->assertCount(1001, $numbers);

        $result = null;
        try {
            $result = $sdk->sendSMS($numbers, 'Test message from PHP SDK live sandbox test (should be rejected)');
        } catch (\Throwable $e) {
            $this->fail('sendSMS with >1000 numbers threw an unhandled exception instead of returning a clean failure: ' . $e->getMessage());
        }

        $this->assertFalse($result, 'the API is expected to reject a request with more than 1000 numbers');
    }

    public function testBalanceMethodsAgainstSandbox()
    {
        $credentials = $this->sandboxCredentials();
        if ($credentials === null) {
            $this->markTestSkipped('COMMS_SANDBOX_USERNAME / COMMS_SANDBOX_API_KEY not set; skipping live sandbox test.');
        }
        [$username, $apiKey] = $credentials;

        CommsSDK::useSandBox();
        $sdk = CommsSDK::authenticate($username, $apiKey);
        $this->assertTrue($sdk->isAuthenticated(), 'authentication against the sandbox failed');

        $response = $sdk->queryBalance();
        $this->assertNotNull($response, 'queryBalance() returned null against the sandbox');
        $this->assertEquals('OK', $response->getStatus());
        $this->assertGreaterThanOrEqual(0, floatval($response->getBalance()));

        $balance = $sdk->getBalance();
        $this->assertIsFloat($balance);
        $this->assertGreaterThanOrEqual(0, $balance);
    }
}
