<?php

namespace PahappaLimited\EgoSmsSdk\v1\utils;

class NumberValidator {
    private static $regex = '/^\\+?(0|\\d{3})\\d{9}$/';

    /**
     * @param string[] $numbers 
     */
    public static function validateNumbers(array $numbers) {
        if (empty($numbers)) {
            error_log('Number list cannot be null or empty');
            return [];
        }

        $_cleansed = [];
        foreach ($numbers as $number) {
            if (empty(trim($number))) {
                error_log(sprintf('Number (%s) cannot be null or empty!', $number));
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
                error_log(sprintf('Number (%s) is not valid!', $number));
            }
        }
        return array_unique($_cleansed);
    }
}
