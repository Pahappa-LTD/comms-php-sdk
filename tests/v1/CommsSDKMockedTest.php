<?php

namespace PahappaLimited\CommsSDK\Tests\v1;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PahappaLimited\CommsSDK\v1\CommsSDK;
use PahappaLimited\CommsSDK\v1\models\MessagePriority;
use PahappaLimited\CommsSDK\v1\models\WalletType;
use PHPUnit\Framework\TestCase;

/**
 * Mocked golden-path tests: no network calls. These exist to catch regressions
 * in the outgoing request shape (e.g. the walletType-omitted-vs-null bug that
 * broke live authentication for Java/Rust) and in response parsing, without
 * depending on the sandbox being reachable.
 */
class CommsSDKMockedTest extends TestCase
{
    /** @var array Captured Guzzle transaction history (request/response pairs), in call order. */
    private array $history = [];

    protected function tearDown(): void
    {
        CommsSDK::setHttpClient(null);
        $this->history = [];
    }

    /**
     * Builds a mock Guzzle client returning the given canned responses in order,
     * installs it as CommsSDK's HTTP client, and records every request made
     * through it into $this->history.
     */
    private function mockClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->history));
        $client = new Client(['handler' => $stack]);
        CommsSDK::setHttpClient($client);
        return $client;
    }

    private function bodyOf(int $index): array
    {
        $request = $this->history[$index]['request'];
        return json_decode((string) $request->getBody(), true);
    }

    private function okResponse(array $body): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body));
    }

    private function authenticate(): CommsSDK
    {
        // Index 0 is always the credential-check Balance call made by authenticate().
        return CommsSDK::authenticate('mockuser', 'mockkey');
    }

    public function testSendSmsRequestAlwaysSendsExplicitLocalWalletType()
    {
        $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']), // auth check
            $this->okResponse(['Status' => 'OK', 'MsgFollowUpUniqueCode' => 'ABC123']), // SendSms
        ]);

        $sdk = $this->authenticate();
        $sdk->querySendSMS('+256772123456', 'Test message');

        $sendSmsBody = $this->bodyOf(1);
        $this->assertSame('SendSms', $sendSmsBody['method']);
        $this->assertArrayHasKey('walletType', $sendSmsBody, 'walletType must always be sent explicitly, never omitted');
        $this->assertSame(WalletType::LOCAL, $sendSmsBody['walletType']);
        $this->assertNotNull($sendSmsBody['walletType'], 'walletType must never be sent as null');
    }

    public function testSendSmsRequestShapeAndDefaultPriority()
    {
        $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']),
            $this->okResponse(['Status' => 'OK', 'MsgFollowUpUniqueCode' => 'ABC123']),
        ]);

        $sdk = $this->authenticate();
        $sdk->querySendSMS(['256772123456'], 'Test message', 'CustomSender');

        $body = $this->bodyOf(1);
        $this->assertSame('mockuser', $body['userdata']['username']);
        $this->assertCount(1, $body['msgdata']);
        $this->assertSame('256772123456', $body['msgdata'][0]['number']);
        $this->assertSame('Test message', $body['msgdata'][0]['message']);
        $this->assertSame('CustomSender', $body['msgdata'][0]['senderid']);
        $this->assertSame(MessagePriority::HIGH, $body['msgdata'][0]['priority'], 'priority must default to HIGH, not HIGHEST');
    }

    public function testSendSmsSuccessResponseIsParsedAndReportsSuccess()
    {
        $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']),
            $this->okResponse(['Status' => 'OK', 'MsgFollowUpUniqueCode' => 'XYZ789']),
        ]);

        $sdk = $this->authenticate();
        $this->assertTrue($sdk->sendSMS('+256772123456', 'Test message'));

        $response = $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']),
            $this->okResponse(['Status' => 'OK', 'MsgFollowUpUniqueCode' => 'XYZ789']),
        ]);
        $sdk2 = $this->authenticate();
        $full = $sdk2->querySendSMS('+256772123456', 'Test message');
        $this->assertSame('OK', $full->getStatus());
        $this->assertSame('XYZ789', $full->getMsgFollowUpUniqueCode());
    }

    public function testSendSmsFailedResponseIsHandledGracefully()
    {
        $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']),
            $this->okResponse(['Status' => 'Failed', 'Message' => 'Insufficient balance']),
        ]);

        $sdk = $this->authenticate();
        $this->assertFalse($sdk->sendSMS('+256772123456', 'Test message'));
    }

    public function testQueryBalanceDefaultsToLocalWalletType()
    {
        $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']), // auth check
            $this->okResponse(['Status' => 'OK', 'Balance' => '50']),  // queryBalance()
        ]);

        $sdk = $this->authenticate();
        $sdk->queryBalance();

        $body = $this->bodyOf(1);
        $this->assertSame('Balance', $body['method']);
        $this->assertSame(WalletType::LOCAL, $body['walletType']);
    }

    public function testQueryBalanceRespectsExplicitInternationalWalletType()
    {
        $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']),
            $this->okResponse(['Status' => 'OK', 'Balance' => '75']),
        ]);

        $sdk = $this->authenticate();
        $sdk->queryBalance(WalletType::INTERNATIONAL);

        $body = $this->bodyOf(1);
        $this->assertSame(WalletType::INTERNATIONAL, $body['walletType']);
    }

    public function testCredentialValidationSendsExplicitLocalWalletType()
    {
        $this->mockClient([
            $this->okResponse(['Status' => 'OK', 'Balance' => '100']),
        ]);

        CommsSDK::authenticate('mockuser', 'mockkey');

        $authBody = $this->bodyOf(0);
        $this->assertSame('Balance', $authBody['method']);
        $this->assertArrayHasKey('walletType', $authBody, 'the credential-check request must always send walletType explicitly');
        $this->assertSame(WalletType::LOCAL, $authBody['walletType']);
    }
}
