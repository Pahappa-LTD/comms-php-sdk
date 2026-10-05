<?php

namespace PahappaLimited\CommsSDK\v1\exceptions;

/**
 * The account credentials were rejected by the server, or could not be verified.
 */
class CommsAuthenticationException extends \RuntimeException implements CommsException
{
}
