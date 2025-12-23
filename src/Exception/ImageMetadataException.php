<?php

namespace App\Exception;

class ImageMetadataException extends \Exception
{
    public function __construct(string $message = 'Image metadata error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
