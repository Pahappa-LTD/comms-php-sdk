<?php

namespace PahappaLimited\EgoSmsSdk\v1;

use PahappaLimited\EgoSmsSdk\v1\models\ApiRequest;
use PahappaLimited\EgoSmsSdk\v1\models\ApiResponse;
use PahappaLimited\EgoSmsSdk\v1\models\MessageModel;
use PahappaLimited\EgoSmsSdk\v1\models\MessagePriority;
use PahappaLimited\EgoSmsSdk\v1\models\UserData;
use PahappaLimited\EgoSmsSdk\v1\utils\NumberValidator;
use PahappaLimited\EgoSmsSdk\v1\utils\Validator;

class EgoSmsSDK
{
    public static $API_URL = 'https://www.egosms.co/api/v1/json/';

    private $apiKey;
    private $username;
    private $password;
    private $senderId = 'EgoSms';
    private $isAuthenticated = false;

    private function __construct() {}

    /**
     * Uses the sandbox api. make an account at "https://sandbox.egosms.co" to use the sandbox.
     * For live, check out {@link EgoSmsSDK::useLiveServer} 
     */
    public static function useSandBox()
    {
        self::$API_URL = 'http://sandbox.egosms.co/api/v1/json/';
    }

    /**
     * Uses the live api (default). make an account at "https://www.egosms.co" to use the live api.
     * For testing, check out {@link EgoSmsSDK::useSandBox} 
     */
    public static function useLiveServer()
    {
        self::$API_URL = 'https://www.egosms.co/api/v1/json/';
    }

    public static function authenticateWithApiKey($apiKey)
    {
        throw new \BadMethodCallException('API Key authentication is not supported in this version. Please use username and password authentication.');
    }

    public static function authenticate($username, $password): EgoSmsSDK
    {
        $sdk = new EgoSmsSDK();
        $sdk->username = $username;
        $sdk->password = $password;
        Validator::validateCredentials($sdk);
        return $sdk;
    }

    public function withSenderId($senderId): EgoSmsSDK
    {
        $this->senderId = $senderId;
        return $this;
    }

    public function sendSMS($numbers, $message, $senderId = null, $priority = MessagePriority::HIGHEST)
    {
        if ($this->sdkNotAuthenticated()) {
            return false;
        }
        if (empty($numbers)) {
            throw new \InvalidArgumentException('Numbers list cannot be null or empty');
        }
        if (empty($message)) {
            throw new \InvalidArgumentException('Message cannot be null or empty');
        }
        if (strlen($message) == 1) {
            throw new \InvalidArgumentException('Message cannot be a single character');
        }
        if (empty(trim($senderId))) {
            $senderId = $this->senderId;
        }

        if (strlen($senderId) > 11) {
            error_log("Warning: Sender ID length exceeds 11 characters. Some networks may truncate or reject messages.");
        }

        $numbers = is_array($numbers) ? $numbers : [$numbers];
        $validatedNumbers = NumberValidator::validateNumbers($numbers);

        if (empty($validatedNumbers)) {
            error_log('No valid phone numbers provided. Please check inputs.');
            return false;
        }

        $apiRequest = new ApiRequest();
        $apiRequest->setMethod('SendSms');

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
        $apiRequest->setUserdata(new UserData($this->username, $this->password));

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post(self::$API_URL, [
                'json' => $apiRequest->toArray(),
            ]);

            $apiResponse = json_decode($response->getBody(), true);

            if ($apiResponse['Status'] === 'OK') {
                echo "SMS sent successfully.\n";
                echo "MessageFollowUpUniqueCode: " . $apiResponse['MsgFollowUpUniqueCode'] . "\n";
                return true;
            } else {
                throw new \Exception($apiResponse['Message']);
            }
        } catch (\Exception $e) {
            error_log("Failed to send SMS: " . $e->getMessage());
            error_log("Request: " . json_encode($apiRequest->toArray()));
            return false;
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

    public function getBalance()
    {
        if ($this->sdkNotAuthenticated()) {
            return null;
        }

        $apiRequest = new ApiRequest();
        $apiRequest->setMethod('Balance');
        $apiRequest->setUserdata(new UserData($this->username, $this->password));

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post(self::$API_URL, [
                'json' => $apiRequest->toArray(),
            ]);

            $apiResponse = json_decode($response->getBody(), true);
            echo "MessageFollowUpUniqueCode: " . $apiResponse['MsgFollowUpUniqueCode'] . "\n";

            return $apiResponse['Balance'];
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get balance: " . $e->getMessage(), 0, $e);
        }
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setAuthenticated($isAuthenticated)
    {
        $this->isAuthenticated = $isAuthenticated;
    }
}
