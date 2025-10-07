<?php

namespace PahappaLimited\CommsSDK\v1\utils;

use PahappaLimited\CommsSDK\v1\CommsSDK;
use PahappaLimited\CommsSDK\v1\models\ApiRequest;
use PahappaLimited\CommsSDK\v1\models\ApiResponse;
use PahappaLimited\CommsSDK\v1\models\UserData;

class Validator {
    public static function validateCredentials(CommsSDK $sdk): bool {
        if ($sdk == null) {
            throw new \InvalidArgumentException('CommsSDK instance cannot be null');
        }
        
        if ($sdk->getApiKey() == null || $sdk->getUserName() == null) {
            throw new \InvalidArgumentException('Either API Key or Username must be provided');
        }
        
        if (!self::isValidCredential($sdk)) {
            echo "                                                      _                    \n";
            echo "  /\     _|_ |_   _  ._ _|_ o  _  _. _|_ o  _  ._    |_ _. o |  _   _| | | \n";
            echo " /--\ |_| |_ | | (/_ | | |_ | (_ (_|  |_ | (_) | |   | (_| | | (/_ (_| o o \n";
            echo "                                                                           \n";
            echo "\n";
            return false;
        }
        
        echo "Credentials validated successfully.\n";
        echo "Validated using basic auth\n";
        $sdk->setAuthenticated();
        return true;
    }

    private static function isValidCredential(CommsSDK $sdk) {
        $apiRequest = new ApiRequest();
        $apiRequest->setMethod('Balance');
        $apiRequest->setUserdata(new UserData($sdk->getUserName(), $sdk->getApiKey()));

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post(CommsSDK::$API_URL, [
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
