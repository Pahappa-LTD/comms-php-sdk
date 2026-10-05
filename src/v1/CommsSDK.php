<?php

namespace PahappaLimited\CommsSDK\v1;

use PahappaLimited\CommsSDK\v1\exceptions\CommsApiException;
use PahappaLimited\CommsSDK\v1\exceptions\CommsValidationException;
use PahappaLimited\CommsSDK\v1\models\ApiRequest;
use PahappaLimited\CommsSDK\v1\models\ApiResponse;
use PahappaLimited\CommsSDK\v1\models\MessageModel;
use PahappaLimited\CommsSDK\v1\models\MessagePriority;
use PahappaLimited\CommsSDK\v1\models\UserData;
use PahappaLimited\CommsSDK\v1\models\WalletType;
use PahappaLimited\CommsSDK\v1\utils\LoggerHolder;
use PahappaLimited\CommsSDK\v1\utils\NetworkHelper;
use PahappaLimited\CommsSDK\v1\utils\NumberValidator;
use PahappaLimited\CommsSDK\v1\utils\Validator;
use Psr\Log\LoggerInterface;

class CommsSDK
{
    public const LIVE_API_URL = "https://comms.egosms.co/api/v1/json";
    public const SANDBOX_API_URL = "https://comms-test.pahappa.net/api/v1/json";

    /**
     * Sets the PSR-3 logger the SDK writes to. The SDK is silent until a logger is set; pass null to silence it again.
     */
    public static function setLogger(?LoggerInterface $logger): void
    {
        LoggerHolder::set($logger);
    }

    /**
     * Global API endpoint, followed by instances created through {@link CommsSDK::authenticate}.
     *
     * @deprecated Each instance now carries its own endpoint. Use {@link CommsSDK::live} or
     *             {@link CommsSDK::sandbox} instead.
     */
    public static $API_URL = self::LIVE_API_URL;

    private $apiKey;
    private $userName;
    private $senderId = "EgoSMS";
    private $isAuthenticated = false;
    /** This instance's own endpoint, or null when it follows {@link CommsSDK::$API_URL}. */
    private ?string $apiUrl = null;

    private static ?\GuzzleHttp\ClientInterface $httpClient = null;

    private function __construct() {}

    /**
     * Overrides the Guzzle client used for all API calls (CommsSDK and Validator).
     * Intended for tests to inject a mocked client; pass null to restore the default.
     */
    public static function setHttpClient(?\GuzzleHttp\ClientInterface $client): void
    {
        self::$httpClient = $client;
    }

    public static function getHttpClient(): \GuzzleHttp\ClientInterface
    {
        return self::$httpClient ?? new \GuzzleHttp\Client();
    }

    /**
     * Creates an instance bound to the live server and verifies the credentials.
     * Check {@link CommsSDK::isAuthenticated} for the credential result.
     *
     * @throws CommsValidationException if the user name or API key is empty.
     */
    public static function live($userName, $apiKey): CommsSDK
    {
        return self::create($userName, $apiKey, self::LIVE_API_URL);
    }

    /**
     * Creates an instance bound to the sandbox server (for testing) and verifies the credentials.
     * Make an account at "https://comms-test.pahappa.net" to use the sandbox.
     * Check {@link CommsSDK::isAuthenticated} for the credential result.
     *
     * @throws CommsValidationException if the user name or API key is empty.
     */
    public static function sandbox($userName, $apiKey): CommsSDK
    {
        return self::create($userName, $apiKey, self::SANDBOX_API_URL);
    }

    private static function create($userName, $apiKey, ?string $apiUrl): CommsSDK
    {
        $sdk = new CommsSDK();
        $sdk->userName = $userName;
        $sdk->apiKey = $apiKey;
        $sdk->apiUrl = $apiUrl;
        $sdk->isAuthenticated = Validator::validateCredentials($sdk);
        return $sdk;
    }

    /**
     * Switches the global endpoint to the sandbox. This also affects existing instances created
     * through {@link CommsSDK::authenticate}.
     *
     * @deprecated Use {@link CommsSDK::sandbox}, which binds one instance without changing global state.
     */
    public static function useSandBox()
    {
        self::$API_URL = self::SANDBOX_API_URL;
    }

    /**
     * Switches the global endpoint to the live server. This also affects existing instances created
     * through {@link CommsSDK::authenticate}.
     *
     * @deprecated Use {@link CommsSDK::live}, which binds one instance without changing global state.
     */
    public static function useLiveServer()
    {
        self::$API_URL = self::LIVE_API_URL;
    }

    /**
     * Authenticates and creates a new instance that follows the global {@link CommsSDK::$API_URL}.
     *
     * @deprecated Use {@link CommsSDK::live} or {@link CommsSDK::sandbox}, which bind the instance to one endpoint.
     * @throws CommsValidationException if the user name or API key is empty.
     */
    public static function authenticate($userName, $apiKey): CommsSDK
    {
        return self::create($userName, $apiKey, null);
    }

    public function setAuthenticated()
    {
        $this->isAuthenticated = true;
    }

    public function withSenderId($senderId): CommsSDK
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function getSenderId()
    {
        return $this->senderId;
    }

    /**
     * @return string The endpoint this instance talks to: its own, or the global {@link CommsSDK::$API_URL} if it follows it.
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl ?? self::$API_URL;
    }

    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /**
     * Sends an SMS to one or more numbers.
     *
     * @return bool true if sent successfully, false otherwise.
     * @throws CommsValidationException if the numbers list or the message is empty, or the message is a single character.
     * @throws CommsApiException if the server replies with a status the SDK does not recognise.
     */
    public function sendSMS(
        $numbers,
        $message,
        $senderId = null,
        $priority = MessagePriority::HIGH,
    ) {

        $apiResponse = $this->querySendSMS(
            $numbers,
            $message,
            $senderId ?: $this->senderId,
            $priority,
        );

        if ($apiResponse === null) {
            LoggerHolder::get()->error("Failed to get a response from the server.");
            return false;
        }

        if ($apiResponse->getStatus() === "OK") {
            LoggerHolder::get()->info("SMS sent successfully.");
            LoggerHolder::get()->info("MessageFollowUpUniqueCode: {code}", [
                "code" => $apiResponse->getMsgFollowUpUniqueCode(),
            ]);
            return true;
        } elseif ($apiResponse->getStatus() === "Failed") {
            LoggerHolder::get()->error("Failed: {message}", ["message" => $apiResponse->getMessage()]);
            return false;
        } else {
            throw new CommsApiException(
                "Unexpected response status: " . $apiResponse->getStatus(),
            );
        }
    }

    /**
     * Same as {@link CommsSDK::sendSMS} but returns the full ApiResponse, or null on error.
     *
     * @throws CommsValidationException if the numbers list or the message is empty, or the message is a single character.
     */
    public function querySendSMS($numbers, $message, $senderId = null, $priority = MessagePriority::HIGH)
    {
        if ($this->sdkNotAuthenticated()) {
            return null;
        }

        $senderId = $senderId ?: $this->senderId;

        if (!is_array($numbers)) {
            $numbers = trim($numbers);
            switch ($numbers) {
                case "":
                    $numbers = [];
                    break;
                default:
                    $numbers = [$numbers];
            }
        }


        if (empty($numbers)) {
            throw new CommsValidationException("Numbers list cannot be empty");
        }
        if (empty($message)) {
            throw new CommsValidationException("Message cannot be empty");
        }
        if (strlen($message) == 1) {
            throw new CommsValidationException(
                "Message cannot be a single character",
            );
        }

        if (empty(trim($senderId))) {
            $senderId = $this->senderId;
        }
        if (strlen($senderId) > 11) {
            LoggerHolder::get()->warning("Warning: Sender ID length exceeds 11 characters. Some networks may truncate or reject messages.");
        }

        $validatedNumbers = NumberValidator::validateNumbers($numbers);

        if (empty($validatedNumbers)) {
            LoggerHolder::get()->error("No valid phone numbers provided. Please check inputs.");
            return null;
        }

        $messageModels = [];
        foreach ($validatedNumbers as $number) {
            $messageModel = new MessageModel();
            $messageModel->setNumber($number);
            $messageModel->setMessage($message);
            $messageModel->setSenderId($senderId);
            $messageModel->setPriority($priority);
            $messageModels[] = $messageModel;
        }

        return $this->sendCustomSMS($messageModels);
    }

    /**
     * Sends a custom-built list of MessageModel objects. Returns null on error.
     *
     * @param MessageModel[] $messageModels
     */
    public function sendCustomSMS(array $messageModels)
    {
        if ($this->sdkNotAuthenticated()) {
            return null;
        }

        $apiRequest = new ApiRequest();
        $apiRequest->setMethod("SendSms");
        $apiRequest->setUserdata(new UserData($this->userName, $this->apiKey));
        $apiRequest->setWalletType(WalletType::LOCAL);
        $apiRequest->setMessageData($messageModels);

        try {
            return ApiResponse::fromArray(NetworkHelper::post($apiRequest, $this->getApiUrl()));
        } catch (\Exception $e) {
            LoggerHolder::get()->error("Failed to send SMS: {message}", ["message" => $e->getMessage(), "exception" => $e]);
            LoggerHolder::get()->debug("Request: {request}", ["request" => json_encode($apiRequest->toArray())]);
            return null;
        }
    }

    private function sdkNotAuthenticated(): bool
    {
        if (!$this->isAuthenticated) {
            LoggerHolder::get()->warning("SDK is not authenticated. Please authenticate before performing actions.");
            LoggerHolder::get()->warning("Attempting to re-authenticate with provided credentials...");
            return !Validator::validateCredentials($this);
        }
        return false;
    }

    /**
     * Same as {@link CommsSDK::getBalance} but returns the full ApiResponse, or null if the
     * credentials could not be verified.
     *
     * @throws CommsApiException if the balance request fails.
     */
    public function queryBalance($walletType = null)
    {
        if ($this->sdkNotAuthenticated()) {
            return null;
        }

        if ($walletType === null) {
            $walletType = WalletType::LOCAL;
        }

        $apiRequest = new ApiRequest();
        $apiRequest->setMethod("Balance");
        $apiRequest->setUserdata(new UserData($this->userName, $this->apiKey));
        $apiRequest->setWalletType($walletType);

        try {
            return ApiResponse::fromArray(NetworkHelper::post($apiRequest, $this->getApiUrl()));
        } catch (\Exception $e) {
            throw new CommsApiException(
                "Failed to get balance: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Gets the SMS account balance for the given wallet, or null if the credentials could not be verified.
     *
     * @throws CommsApiException if the balance request fails.
     */
    public function getBalance($walletType = null)
    {
        $response = $this->queryBalance($walletType);
        return $response && $response->getBalance()
            ? floatval($response->getBalance())
            : null;
    }

    public function __toString()
    {
        return "SDK({$this->userName}, {$this->senderId}, {$this->getApiUrl()})";
    }
}
