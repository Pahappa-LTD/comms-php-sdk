<?php

namespace PahappaLimited\CommsSDK\v1\utils;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use PahappaLimited\CommsSDK\v1\CommsSDK;
use PahappaLimited\CommsSDK\v1\exceptions\CommsApiException;
use PahappaLimited\CommsSDK\v1\models\ApiRequest;

class NetworkHelper
{
    /**
     * Posts the request as JSON and returns the decoded response body.
     *
     * @throws CommsApiException if the server cannot be reached, replies with a non-2xx status, or the
     *                           reply is not JSON. The server's `Message` field is used as the exception
     *                           message when present.
     */
    public static function post(ApiRequest $apiRequest, string $apiUrl): array
    {
        try {
            $response = CommsSDK::getHttpClient()->post($apiUrl, [
                'json' => $apiRequest->toArray(),
            ]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $failed = $e->getResponse();
                throw new CommsApiException(self::describeFailure($failed->getStatusCode(), (string) $failed->getBody()), 0, $e);
            }
            throw new CommsApiException("Could not complete request to {$apiUrl}: " . $e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new CommsApiException("Could not complete request to {$apiUrl}: " . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            throw new CommsApiException(self::describeFailure($status, $body));
        }
        return $data;
    }

    private static function describeFailure(int $status, string $body): string
    {
        $data = json_decode($body, true);
        if (is_array($data) && isset($data['Message']) && is_string($data['Message']) && $data['Message'] !== '') {
            return $data['Message'];
        }
        return "HTTP {$status}" . ($body === '' ? '' : ": {$body}");
    }
}
