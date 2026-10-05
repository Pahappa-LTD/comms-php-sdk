<?php

namespace PahappaLimited\CommsSDK\v1\exceptions;

/**
 * A request could not be completed, or the server's response was unusable.
 * Also a RuntimeException, which the SDK threw for these cases before.
 */
class CommsApiException extends \RuntimeException implements CommsException
{
}
