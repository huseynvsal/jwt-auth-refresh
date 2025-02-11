<?php

namespace Huseynvsal\JwtAuthRefresh\Exceptions;

use Exception;

class InvalidTokenException extends Exception
{
    public function __construct(string $message = "Invalid or expired token.", int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
