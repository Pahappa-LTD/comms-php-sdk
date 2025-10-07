<?php

namespace PahappaLimited\CommsSDK\v1\models;

class MessageModel {
    private string $number;
    private string $message;
    private string $senderId;
    private string $priority;

    public function getNumber() {
        return $this->number;
    }

    public function setNumber($number) {
        $this->number = $number;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getSenderId() {
        return $this->senderId;
    }

    public function setSenderId($senderId) {
        $this->senderId = $senderId;
    }

    public function getPriority() {
        return $this->priority;
    }

    public function setPriority($priority) {
        $this->priority = $priority;
    }

    public function toArray() {
        return [
            'number' => $this->number,
            'message' => $this->message,
            'senderid' => $this->senderId,
            'priority' => $this->priority,
        ];
    }
}