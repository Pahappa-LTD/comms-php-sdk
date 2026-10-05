<?php

namespace PahappaLimited\CommsSDK\v1\exceptions;

/**
 * The SDK rejected the input before any request was sent.
 * Also an InvalidArgumentException, which the SDK threw for these cases before.
 */
class CommsValidationException extends \InvalidArgumentException implements CommsException
{
}
