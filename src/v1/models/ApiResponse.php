<?php

namespace PahappaLimited\CommsSDK\v1\models;

class ApiResponse {
    private string $status;
    private ?string $message;
    private ?int $cost;
    private ?string $currency;
    private ?string $msgFollowUpUniqueCode;
    private ?string $balance;

    public function __construct($status, $message = null, $cost = null, $currency = null, $msgFollowUpUniqueCode = null, $balance = null) {
        $this->status = $status;
        $this->message = $message;
        $this->cost = $cost;
        $this->currency = $currency;
        $this->msgFollowUpUniqueCode = $msgFollowUpUniqueCode;
        $this->balance = $balance;
    }

    public static function fromArray($data) {
        return new self(
            $data['Status'] ?? '',
            $data['Message'] ?? null,
            isset($data['Cost']) ? intval($data['Cost']) : null,
            $data['Currency'] ?? null,
            $data['MsgFollowUpUniqueCode'] ?? null,
            $data['Balance'] ?? null
        );
    }

    public function getStatus() {
        return $this->status;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getCost() {
        return $this->cost;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function getMsgFollowUpUniqueCode() {
        return $this->msgFollowUpUniqueCode;
    }

    public function getBalance() {
        return $this->balance;
    }

    public function toArray() {
        $result = ['Status' => $this->status];
        if ($this->message !== null) $result['Message'] = $this->message;
        if ($this->cost !== null) $result['Cost'] = $this->cost;
        if ($this->currency !== null) $result['Currency'] = $this->currency;
        if ($this->msgFollowUpUniqueCode !== null) $result['MsgFollowUpUniqueCode'] = $this->msgFollowUpUniqueCode;
        if ($this->balance !== null) $result['Balance'] = $this->balance;
        return $result;
    }
}