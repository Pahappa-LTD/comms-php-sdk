<?php

namespace PahappaLimited\CommsSDK\v1\utils;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Holds the PSR-3 logger the SDK writes to. Set it through {@link \PahappaLimited\CommsSDK\v1\CommsSDK::setLogger}.
 *
 * @internal
 */
final class LoggerHolder
{
    private static ?LoggerInterface $logger = null;

    public static function get(): LoggerInterface
    {
        return self::$logger ??= new NullLogger();
    }

    public static function set(?LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }
}
