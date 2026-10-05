<?php

namespace PahappaLimited\CommsSDK\v1\utils;

use PahappaLimited\CommsSDK\v1\CommsSDK;
use PahappaLimited\CommsSDK\v1\exceptions\CommsApiException;
use PahappaLimited\CommsSDK\v1\exceptions\CommsValidationException;
use PahappaLimited\CommsSDK\v1\models\ApiRequest;
use PahappaLimited\CommsSDK\v1\models\UserData;
use PahappaLimited\CommsSDK\v1\models\WalletType;

class Validator {
    /**
     * Verifies the SDK's credentials against the server it is bound to.
     *
     * @return bool true if the server accepted the credentials, false otherwise.
     * @throws CommsValidationException if the SDK instance, its user name or its API key is missing.
     */
    public static function validateCredentials(CommsSDK $sdk): bool {
        if ($sdk == null) {
            throw new CommsValidationException('CommsSDK instance cannot be null');
        }

        if ($sdk->getApiKey() == null || $sdk->getUserName() == null) {
            throw new CommsValidationException('Either API Key or Username must be provided');
        }

        if (!self::isValidCredential($sdk)) {
            LoggerHolder::get()->error("Authentication failed");
            return false;
        }

        LoggerHolder::get()->info("Credentials validated successfully.");
        LoggerHolder::get()->info("Validated using basic auth");
        $sdk->setAuthenticated();
        return true;
    }

    private static function isValidCredential(CommsSDK $sdk) {
        $apiRequest = new ApiRequest();
        $apiRequest->setMethod('Balance');
        $apiRequest->setUserdata(new UserData($sdk->getUserName(), $sdk->getApiKey()));
        $apiRequest->setWalletType(WalletType::LOCAL);

        try {
            $apiResponse = NetworkHelper::post($apiRequest, $sdk->getApiUrl());

            if (($apiResponse['Status'] ?? null) === 'OK') {
                LoggerHolder::get()->info("Credentials validated successfully.");
                return true;
            }
            else {
                throw new CommsApiException($apiResponse['Message'] ?? 'Authentication failed');
            }
        } catch (\Exception $e) {
            LoggerHolder::get()->error("Error validating credentials: {message}", ["message" => $e->getMessage(), "exception" => $e]);
            return false;
        }
    }
}
