<?php

namespace PahappaLimited\CommsSDK\v1\models;

class ApiResponse {
    private string $status;
    private ?string $message;
    private ?float $cost;
    private ?string $msgFollowUpUniqueCode;
    private ?string $balance;

    public function __construct($status, $message = null, $cost = null, $msgFollowUpUniqueCode = null, $balance = null) {
        $this->status = $status;
        $this->message = $message;
        $this->cost = $cost;
        $this->msgFollowUpUniqueCode = $msgFollowUpUniqueCode;
        $this->balance = $balance;
    }

    public static function fromArray($data) {
        return new self(
            $data['Status'] ?? '',
            $data['Message'] ?? null,
            isset($data['Cost']) ? floatval($data['Cost']) : null,
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
        if ($this->msgFollowUpUniqueCode !== null) $result['MsgFollowUpUniqueCode'] = $this->msgFollowUpUniqueCode;
        if ($this->balance !== null) $result['Balance'] = $this->balance;
        return $result;
    }
}