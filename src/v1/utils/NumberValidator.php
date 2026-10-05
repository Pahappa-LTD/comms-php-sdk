<?php

namespace PahappaLimited\CommsSDK\v1\utils;

class NumberValidator {
    private static $regex = '/^\\+?(0|\\d{3})\\d{9}$/';

    /**
     * @param string[] $numbers 
     */
    public static function validateNumbers(array $numbers) {
        if (empty($numbers)) {
            LoggerHolder::get()->warning('Number list cannot be null or empty');
            return [];
        }

        $_cleansed = [];
        foreach ($numbers as $number) {
            if (empty(trim($number))) {
                LoggerHolder::get()->warning('Number ({number}) cannot be null or empty!', ['number' => $number]);
                continue;
            }
            $number = preg_replace('/-|\\s/', '', trim($number));
            if (preg_match(self::$regex, $number)) {
                if (substr($number, 0, 1) === '0') {
                    $number = '256' . substr($number, 1);
                } elseif (substr($number, 0, 1) === '+') {
                    $number = substr($number, 1);
                }
                $_cleansed[] = $number;
            } else {
                LoggerHolder::get()->error('Number ({number}) is not valid!', ['number' => $number]);
            }
        }
        return array_unique($_cleansed);
    }
}
