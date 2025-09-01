<?php

namespace PahappaLimited\EgoSmsSdk\v1\models;

class ApiResponse {
    /**@var ApiResponseCode */
    private string $status;
    private string $message;
    private string $cost;
    private string $MsgFollowUpUniqueCode;
    private string $balance;

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getCost() {
        return $this->cost;
    }

    public function setCost($cost) {
        $this->cost = $cost;
    }

    public function getMsgFollowUpUniqueCode() {
        return $this->MsgFollowUpUniqueCode;
    }

    public function setMsgFollowUpUniqueCode($MsgFollowUpUniqueCode) {
        $this->MsgFollowUpUniqueCode = $MsgFollowUpUniqueCode;
    }

    public function getBalance() {
        return $this->balance;
    }

    public function setBalance($balance) {
        $this->balance = $balance;
    }
}