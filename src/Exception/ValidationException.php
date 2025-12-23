<?php

namespace App\Exception;

class ValidationException extends \Exception
{
    public function __construct(string $message = 'Validation error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
