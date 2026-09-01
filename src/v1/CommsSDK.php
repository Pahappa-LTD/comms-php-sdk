<?php

namespace PahappaLimited\CommsSDK\v1;

use PahappaLimited\CommsSDK\v1\models\ApiRequest;
use PahappaLimited\CommsSDK\v1\models\ApiResponse;
use PahappaLimited\CommsSDK\v1\models\MessageModel;
use PahappaLimited\CommsSDK\v1\models\MessagePriority;
use PahappaLimited\CommsSDK\v1\models\UserData;
use PahappaLimited\CommsSDK\v1\models\WalletType;
use PahappaLimited\CommsSDK\v1\utils\NumberValidator;
use PahappaLimited\CommsSDK\v1\utils\Validator;

class CommsSDK
{
    public static $API_URL = "https://comms.egosms.co/api/v1/json/";

    private $apiKey;
    private $userName;
    private $senderId = "EgoSMS";
    private $isAuthenticated = false;

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
     * Uses the sandbox api. make an account at "https://comms-test.pahappa.net" to use the sandbox.
     * For live, check out {@link CommsSDK::useLiveServer}
     */
    public static function useSandBox()
    {
        self::$API_URL = 'https://comms-test.pahappa.net/api/v1/json';
    }

    /**
     * Uses the live api (default). make an account at "https://comms.egosms.co" to use the live api.
     * For testing, check out {@link CommsSDK::useSandBox}
     */
    public static function useLiveServer()
    {
        self::$API_URL = "https://comms.egosms.co/api/v1/json";
    }

    public static function authenticate($userName, $apiKey): CommsSDK
    {
        $sdk = new CommsSDK();
        $sdk->userName = $userName;
        $sdk->apiKey = $apiKey;
        Validator::validateCredentials($sdk);
        return $sdk;
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

    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

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
            echo "Failed to get a response from the server.\n";
            return false;
        }

        if ($apiResponse->getStatus() === "OK") {
            echo "SMS sent successfully.\n";
            echo "MessageFollowUpUniqueCode: " .
                $apiResponse->getMsgFollowUpUniqueCode() .
                "\n";
            return true;
        } elseif ($apiResponse->getStatus() === "Failed") {
            echo "Failed: " . $apiResponse->getMessage() . "\n";
            return false;
        } else {
            throw new \RuntimeException(
                "Unexpected response status: " . $apiResponse->getStatus(),
            );
        }
    }

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
            throw new \InvalidArgumentException("Numbers list cannot be empty");
        }
        if (empty($message)) {
            throw new \InvalidArgumentException("Message cannot be empty");
        }
        if (strlen($message) == 1) {
            throw new \InvalidArgumentException(
                "Message cannot be a single character",
            );
        }

        if (empty(trim($senderId))) {
            $senderId = $this->senderId;
        }
        if (strlen($senderId) > 11) {
            echo "Warning: Sender ID length exceeds 11 characters. Some networks may truncate or reject messages.\n";
        }

        $validatedNumbers = NumberValidator::validateNumbers($numbers);

        if (empty($validatedNumbers)) {
            error_log("No valid phone numbers provided. Please check inputs.");
            return null;
        }

        $apiRequest = new ApiRequest();
        $apiRequest->setMethod("SendSms");
        $apiRequest->setUserdata(new UserData($this->userName, $this->apiKey));
        $apiRequest->setWalletType(WalletType::LOCAL);

        $messageModels = [];
        foreach ($validatedNumbers as $number) {
            $messageModel = new MessageModel();
            $messageModel->setNumber($number);
            $messageModel->setMessage($message);
            $messageModel->setSenderId($senderId);
            $messageModel->setPriority($priority);
            $messageModels[] = $messageModel;
        }
        $apiRequest->setMessageData($messageModels);

        try {
            $client = self::getHttpClient();
            $response = $client->post(self::$API_URL, [
                "json" => $apiRequest->toArray(),
            ]);

            $responseData = json_decode($response->getBody(), true);
            return ApiResponse::fromArray($responseData);
        } catch (\Exception $e) {
            error_log("Failed to send SMS: " . $e->getMessage());
            try {
                error_log("Request: " . json_encode($apiRequest->toArray()));
            } catch (\Exception $ignored) {
                // Ignore serialization errors
            }
            return null;
        }
    }

    private function sdkNotAuthenticated(): bool
    {
        if (!$this->isAuthenticated) {
            error_log(
                "SDK is not authenticated. Please authenticate before performing actions.",
            );
            error_log(
                "Attempting to re-authenticate with provided credentials...",
            );
            return !Validator::validateCredentials($this);
        }
        return false;
    }

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
            $client = self::getHttpClient();
            $response = $client->post(self::$API_URL, [
                "json" => $apiRequest->toArray(),
            ]);

            $responseData = json_decode($response->getBody(), true);
            return ApiResponse::fromArray($responseData);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Failed to get balance: " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    public function getBalance($walletType = null)
    {
        $response = $this->queryBalance($walletType);
        return $response && $response->getBalance()
            ? floatval($response->getBalance())
            : null;
    }

    public function __toString()
    {
        return "SDK({$this->userName} => {$this->apiKey})";
    }
}
