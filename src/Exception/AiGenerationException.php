<?php

namespace App\Exception;

class AiGenerationException extends \Exception
{
    public function __construct(string $message = 'AI generation error', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function providerNotConfigured(string $provider): self
    {
        return new self(sprintf('Le provider IA "%s" n\'est pas configuré correctement.', $provider));
    }

    public static function generationFailed(string $provider, string $reason): self
    {
        return new self(sprintf('La génération IA via "%s" a échoué: %s', $provider, $reason));
    }

    public static function invalidResponse(string $provider): self
    {
        return new self(sprintf('Réponse invalide du provider IA "%s".', $provider));
    }

    public static function downloadFailed(string $url, string $reason): self
    {
        return new self(sprintf('Échec du téléchargement de l\'image depuis "%s": %s', $url, $reason));
    }
}
