<?php

namespace PahappaLimited\CommsSDK\Tests\v1;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PahappaLimited\CommsSDK\v1\CommsSDK;
use PahappaLimited\CommsSDK\v1\exceptions\CommsApiException;
use PahappaLimited\CommsSDK\v1\exceptions\CommsAuthenticationException;
use PahappaLimited\CommsSDK\v1\exceptions\CommsException;
use PahappaLimited\CommsSDK\v1\exceptions\CommsValidationException;
use PahappaLimited\CommsSDK\v1\models\ApiRequest;
use PahappaLimited\CommsSDK\v1\models\MessagePriority;
use PahappaLimited\CommsSDK\v1\models\UserData;
use PahappaLimited\CommsSDK\v1\models\WalletType;
use PahappaLimited\CommsSDK\v1\utils\NetworkHelper;
use PahappaLimited\CommsSDK\v1\utils\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Instance-based endpoints, legacy API compatibility, typed exceptions and NetworkHelper failures.
 */
class CommsSDKExceptionsTest extends TestCase
{
    private array $history = [];
    private string $originalApiUrl;

    protected function setUp(): void
    {
        $this->originalApiUrl = CommsSDK::$API_URL;
    }

    protected function tearDown(): void
    {
        CommsSDK::setHttpClient(null);
        CommsSDK::$API_URL = $this->originalApiUrl;
        $this->history = [];
    }

    private function mockClient(array $responses): void
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));
        CommsSDK::setHttpClient(new Client(['handler' => $stack]));
    }

    private function json(int $status, array $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    private function okBalance(): Response
    {
        return $this->json(200, ['Status' => 'OK', 'Message' => 'Success', 'Balance' => '100']);
    }

    private function requestedUrls(): array
    {
        return array_map(fn ($entry) => (string) $entry['request']->getUri(), $this->history);
    }

    private function balanceRequest(): ApiRequest
    {
        $request = new ApiRequest();
        $request->setMethod('Balance');
        $request->setUserdata(new UserData('user', 'key'));
        $request->setWalletType(WalletType::LOCAL);
        return $request;
    }

    public function testEveryPreRefactorPublicMemberStillWorks()
    {
        $this->mockClient(array_fill(0, 8, $this->okBalance()));
        CommsSDK::useSandBox();
        $this->assertSame(CommsSDK::SANDBOX_API_URL, CommsSDK::$API_URL);
        CommsSDK::useLiveServer();
        $this->assertSame(CommsSDK::LIVE_API_URL, CommsSDK::$API_URL);

        $sdk = CommsSDK::authenticate('user', 'secret-key');
        $this->assertTrue($sdk->isAuthenticated());
        $this->assertSame('user', $sdk->getUserName());
        $this->assertSame('secret-key', $sdk->getApiKey());
        $this->assertSame('EgoSMS', $sdk->withSenderId('EgoSMS')->getSenderId());
        $this->assertStringNotContainsString('secret-key', (string) $sdk);
        $this->assertNotNull($sdk->queryBalance());
        $this->assertSame(100.0, $sdk->getBalance(WalletType::LOCAL));
    }

    public function testLegacyInstanceFollowsTheGlobalUrlEvenAfterCreation()
    {
        $this->mockClient([$this->okBalance(), $this->okBalance()]);
        CommsSDK::$API_URL = 'http://first.test/';
        $sdk = CommsSDK::authenticate('user', 'key');

        CommsSDK::$API_URL = 'http://second.test/';
        $sdk->queryBalance();

        $this->assertSame(['http://first.test/', 'http://second.test/'], $this->requestedUrls());
        $this->assertSame('http://second.test/', $sdk->getApiUrl());
    }

    public function testLiveAndSandboxInstancesIgnoreTheGlobalUrl()
    {
        $this->mockClient(array_fill(0, 4, $this->okBalance()));
        CommsSDK::$API_URL = 'http://never-called.test/';

        $live = CommsSDK::live('user', 'key');
        $sandbox = CommsSDK::sandbox('user', 'key');
        $live->queryBalance();
        $sandbox->queryBalance();

        $this->assertSame(
            [CommsSDK::LIVE_API_URL, CommsSDK::SANDBOX_API_URL, CommsSDK::LIVE_API_URL, CommsSDK::SANDBOX_API_URL],
            $this->requestedUrls(),
        );
    }

    public function testLiveWithRejectedCredentialsReturnsAnUnauthenticatedInstance()
    {
        $this->mockClient([$this->json(400, ['Status' => 'Failed', 'Message' => 'Invalid credentials'])]);

        $this->assertFalse(CommsSDK::live('baduser', 'badkey')->isAuthenticated());
    }

    public function testQueryBalanceWrapsAnUnreachableServer()
    {
        $this->mockClient([
            $this->okBalance(),
            new ConnectException('Connection refused', new Request('POST', CommsSDK::LIVE_API_URL)),
        ]);
        $sdk = CommsSDK::live('user', 'key');

        try {
            $sdk->queryBalance();
            $this->fail('expected CommsApiException');
        } catch (CommsApiException $e) {
            $this->assertStringStartsWith('Failed to get balance', $e->getMessage());
            $this->assertNotNull($e->getPrevious());
            $this->assertInstanceOf(\RuntimeException::class, $e);
        }
    }

    public function testRejectedCredentialsOnBalanceSurfaceTheServersMessage()
    {
        $this->mockClient([
            $this->okBalance(),
            $this->json(400, ['Status' => 'Failed', 'Message' => 'Invalid credentials']),
        ]);
        $sdk = CommsSDK::live('user', 'key');

        $this->expectException(CommsApiException::class);
        $this->expectExceptionMessage('Failed to get balance: Invalid credentials');
        $sdk->queryBalance();
    }

    public function testBadInputIsAValidationExceptionAndStillAnInvalidArgumentException()
    {
        $this->mockClient([$this->okBalance()]);
        $sdk = CommsSDK::live('user', 'key');

        foreach ([['256700000000', ''], ['256700000000', 'x'], [[], 'Hello']] as [$numbers, $message]) {
            try {
                $sdk->querySendSMS($numbers, $message);
                $this->fail('expected CommsValidationException');
            } catch (CommsValidationException $e) {
                $this->assertInstanceOf(\InvalidArgumentException::class, $e);
            }
        }
    }

    public function testFactoriesRejectEmptyCredentials()
    {
        $this->expectException(CommsValidationException::class);
        CommsSDK::live('', 'key');
    }

    public function testUnknownResponseStatusIsAnApiException()
    {
        $this->mockClient([
            $this->okBalance(),
            $this->json(200, ['Status' => 'Pending', 'Message' => '?']),
        ]);
        $sdk = CommsSDK::live('user', 'key');

        $this->expectException(CommsApiException::class);
        $sdk->sendSMS('256700000000', 'Hello', 'MyApp', MessagePriority::HIGH);
    }

    public function testEveryTypedExceptionIsACommsException()
    {
        foreach ([CommsAuthenticationException::class, CommsValidationException::class, CommsApiException::class] as $class) {
            $this->assertInstanceOf(CommsException::class, new $class('x'));
        }
    }

    public function testNetworkHelperFallsBackToTheStatusAndBodyForANonJsonReply()
    {
        $this->mockClient([new Response(502, [], 'Bad Gateway')]);

        $this->expectException(CommsApiException::class);
        $this->expectExceptionMessage('HTTP 502: Bad Gateway');
        NetworkHelper::post($this->balanceRequest(), 'http://mock.test/');
    }

    public function testNetworkHelperNamesTheUrlAndKeepsTheCauseWhenUnreachable()
    {
        $cause = new ConnectException('Connection refused', new Request('POST', 'http://127.0.0.1:1/'));
        $this->mockClient([$cause]);

        try {
            NetworkHelper::post($this->balanceRequest(), 'http://127.0.0.1:1/');
            $this->fail('expected CommsApiException');
        } catch (CommsApiException $e) {
            $this->assertSame($cause, $e->getPrevious());
            $this->assertStringContainsString('http://127.0.0.1:1/', $e->getMessage());
        }
    }

    public function testValidatorRejectsMissingCredentials()
    {
        $this->expectException(CommsValidationException::class);
        CommsSDK::authenticate(null, 'key');
    }
}
