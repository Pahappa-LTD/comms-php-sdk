<?php

namespace PahappaLimited\EgoSmsSdk\v1\models;

class UserData {
    private string $username;
    private string $password;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function toArray() {
        return [
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}