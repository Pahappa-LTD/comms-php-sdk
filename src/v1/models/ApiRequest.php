<?php

namespace PahappaLimited\CommsSDK\v1\models;

class ApiRequest {
    private string $method;
    private UserData $userdata;
    /**@var MessageModel[] */
    private array $messageData = [];

    public function getMethod() {
        return $this->method;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function getUserdata() {
        return $this->userdata;
    }

    public function setUserdata($userdata) {
        $this->userdata = $userdata;
    }

    public function getMessageData() {
        return $this->messageData;
    }

    public function setMessageData($messageData) {
        $this->messageData = $messageData;
    }

    public function toArray() {
        $result = [
            'method' => $this->method,
            'userdata' => $this->userdata->toArray(),
        ];
        
        if (!empty($this->messageData)) {
            $result['msgdata'] = array_map(function($message) {
                return $message->toArray();
            }, $this->messageData);
        }
        
        return $result;
    }
}