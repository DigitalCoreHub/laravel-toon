<?php

namespace DigitalCoreHub\Toon\Exceptions;

use Exception;

class InvalidToonFormatException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Invalid TOON format', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
