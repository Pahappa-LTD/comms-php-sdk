<?php

namespace PahappaLimited\CommsSDK\v1;

use PahappaLimited\CommsSDK\v1\models\ApiRequest;
use PahappaLimited\CommsSDK\v1\models\ApiResponse;
use PahappaLimited\CommsSDK\v1\models\MessageModel;
use PahappaLimited\CommsSDK\v1\models\MessagePriority;
use PahappaLimited\CommsSDK\v1\models\UserData;
use PahappaLimited\CommsSDK\v1\utils\NumberValidator;
use PahappaLimited\CommsSDK\v1\utils\Validator;

class CommsSDK
{
    public static $API_URL = 'http://176.58.101.43:8080/communications/api/v1/json/';

    private $apiKey;
    private $userName;
    private $senderId = 'EgoSMS';
    private $isAuthenticated = false;

    private function __construct() {}

    /**
     * Uses the sandbox api. make an account at "https://sandbox.egosms.co" to use the sandbox.
     * For live, check out {@link CommsSDK::useLiveServer} 
     */
    public static function useSandBox()
    {
        self::$API_URL = 'http://176.58.101.43:8080/communications/api/v1/json';
    }

    /**
     * Uses the live api (default). make an account at "https://www.egosms.co" to use the live api.
     * For testing, check out {@link CommsSDK::useSandBox} 
     */
    public static function useLiveServer()
    {
        self::$API_URL = 'http://176.58.101.43:8080/communications/api/v1/json';
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

    public function sendSMS($numbers, $message, $senderId = null, $priority = MessagePriority::HIGHEST)
    {
        $numbers = is_array($numbers) ? $numbers : [$numbers];
        
        $apiResponse = $this->querySendSMS($numbers, $message, $senderId ?: $this->senderId, $priority);
        
        if ($apiResponse === null) {
            echo "Failed to get a response from the server.\n";
            return false;
        }
        
        if ($apiResponse->getStatus() === 'OK') {
            echo "SMS sent successfully.\n";
            echo "MessageFollowUpUniqueCode: " . $apiResponse->getMsgFollowUpUniqueCode() . "\n";
            return true;
        } elseif ($apiResponse->getStatus() === 'Failed') {
            echo "Failed: " . $apiResponse->getMessage() . "\n";
            return false;
        } else {
            throw new \RuntimeException("Unexpected response status: " . $apiResponse->getStatus());
        }
    }

    public function querySendSMS($numbers, $message, $senderId, $priority)
    {
        if ($this->sdkNotAuthenticated()) {
            return null;
        }
        
        if (empty($numbers)) {
            throw new \InvalidArgumentException('Numbers list cannot be empty');
        }
        if (empty($message)) {
            throw new \InvalidArgumentException('Message cannot be empty');
        }
        if (strlen($message) == 1) {
            throw new \InvalidArgumentException('Message cannot be a single character');
        }
        
        if (empty(trim($senderId))) {
            $senderId = $this->senderId;
        }
        if (strlen($senderId) > 11) {
            echo "Warning: Sender ID length exceeds 11 characters. Some networks may truncate or reject messages.\n";
        }

        $validatedNumbers = NumberValidator::validateNumbers($numbers);

        if (empty($validatedNumbers)) {
            error_log('No valid phone numbers provided. Please check inputs.');
            return null;
        }

        $apiRequest = new ApiRequest();
        $apiRequest->setMethod('SendSms');
        $apiRequest->setUserdata(new UserData($this->userName, $this->apiKey));

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
            $client = new \GuzzleHttp\Client();
            $response = $client->post(self::$API_URL, [
                'json' => $apiRequest->toArray(),
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
            error_log('SDK is not authenticated. Please authenticate before performing actions.');
            error_log('Attempting to re-authenticate with provided credentials...');
            return !Validator::validateCredentials($this);
        }
        return false;
    }

    public function queryBalance()
    {
        if ($this->sdkNotAuthenticated()) {
            return null;
        }

        $apiRequest = new ApiRequest();
        $apiRequest->setMethod('Balance');
        $apiRequest->setUserdata(new UserData($this->userName, $this->apiKey));

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post(self::$API_URL, [
                'json' => $apiRequest->toArray(),
            ]);

            $responseData = json_decode($response->getBody(), true);
            return ApiResponse::fromArray($responseData);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get balance: " . $e->getMessage(), 0, $e);
        }
    }

    public function getBalance()
    {
        $response = $this->queryBalance();
        return ($response && $response->getBalance()) ? floatval($response->getBalance()) : null;
    }

    public function __toString()
    {
        return "SDK({$this->userName} => {$this->apiKey})";
    }
}