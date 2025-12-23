<?php

namespace App\Exception;

class AiGenerationException extends \Exception
{
    public function __construct(string $message = 'AI generation error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
