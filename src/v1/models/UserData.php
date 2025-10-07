<?php

namespace PahappaLimited\CommsSDK\v1\models;

class UserData {
    private string $username;
    private string $apikey;

    public function __construct($username, $apikey) {
        $this->username = $username;
        $this->apikey = $apikey;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getApikey() {
        return $this->apikey;
    }

    public function toArray() {
        return [
            'username' => $this->username,
            'password' => $this->apikey,  // Maps to "password" in JSON
        ];
    }
}