<?php

namespace PahappaLimited\EgoSmsSdk\v1\utils;

use PahappaLimited\EgoSmsSdk\v1\EgoSmsSDK;
use PahappaLimited\EgoSmsSdk\v1\models\ApiRequest;
use PahappaLimited\EgoSmsSdk\v1\models\ApiResponse;
use PahappaLimited\EgoSmsSdk\v1\models\UserData;

class Validator {
    public static function validateCredentials(EgoSmsSDK $sdk): bool {
        if ($sdk == null) {
            throw new \InvalidArgumentException('EgoSmsSDK instance cannot be null');
        }
        $isApiKey = true;
        if ($sdk->getApiKey() == null) {
            if ($sdk->getPassword() == null || $sdk->getUsername() == null) {
                throw new \InvalidArgumentException('Either API Key or Username and Password must be provided');
            } else {
                $isApiKey = false;
            }
        }
        if (!self::isValidCredential($sdk, $isApiKey)) {
            echo "                                                      _                    \n";
            echo "  /\     _|_ |_   _  ._ _|_ o  _  _. _|_ o  _  ._    |_ _. o |  _   _| | | \n";
            echo " /--\ |_| |_ | | (/_ | | |_ | (_ (_|  |_ | (_) | |   | (_| | | (/_ (_| o o \n";
            echo "                                                                           \n";
            echo "\n";
            return false;
        }
        echo $isApiKey ? "Validated using an api key" : "Validated using basic auth";
        echo "\n";
        $sdk->setAuthenticated(true);
        return true;
    }

    private static function isValidCredential(EgoSmsSDK $sdk, bool $isApiKey) {
        $apiRequest = new ApiRequest();
        $apiRequest->setMethod('Balance');
        $apiRequest->setUserdata(new UserData($sdk->getUsername(), $sdk->getPassword()));

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post(EgoSmsSDK::$API_URL, [
                'json' => $apiRequest->toArray(),
            ]);

            $apiResponse = json_decode($response->getBody(), true);

            if ($apiResponse['Status'] === 'OK') {
                echo "Credentials validated successfully.\n";
                return true;
            }
            else {
                throw new \Exception($apiResponse['Message']);
            }
        } catch (\Exception $e) {
            error_log("Error validating credentials: " . $e->getMessage() . "\n");
            return false;
        }
    }
}
