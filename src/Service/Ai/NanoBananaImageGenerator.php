<?php

namespace App\Service\Ai;

use App\DTO\AiImageGenerationResult;
use App\Exception\AiGenerationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Adapter pour le provider NanoBanana
 * 
 * Ce service est découplé du CMS et peut être remplacé par un autre provider
 * sans modifier le reste de l'application.
 */
class NanoBananaImageGenerator implements AiImageGeneratorInterface
{
    private const BASE_URL = 'https://api.nanobanana.com/v1';
    private const DEFAULT_MODEL = 'flux-pro';
    private const DEFAULT_SIZE = '1024x1024';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ?string $apiKey = null,
        private ?string $model = null
    ) {
    }

    public function generate(string $prompt, array $options = []): AiImageGenerationResult
    {
        if (!$this->isConfigured()) {
            throw AiGenerationException::providerNotConfigured('NanoBanana');
        }

        try {
            $response = $this->httpClient->request('POST', self::BASE_URL . '/generate', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'prompt' => $prompt,
                    'negative_prompt' => $options['negative_prompt'] ?? null,
                    'model' => $options['model'] ?? $this->model ?? self::DEFAULT_MODEL,
                    'size' => $options['size'] ?? self::DEFAULT_SIZE,
                    'num_images' => 1,
                ],
            ]);

            $data = $response->toArray();

            // NanoBanana retourne généralement un ID de génération et un statut
            return new AiImageGenerationResult(
                generationId: $data['id'] ?? uniqid('nano_'),
                status: $this->mapStatus($data['status'] ?? 'pending'),
                imageUrl: $data['image_url'] ?? $data['images'][0]['url'] ?? null,
                metadata: [
                    'provider' => 'nanobanana',
                    'model' => $options['model'] ?? $this->model ?? self::DEFAULT_MODEL,
                    'size' => $options['size'] ?? self::DEFAULT_SIZE,
                    'raw_response' => $data,
                ]
            );

        } catch (\Exception $e) {
            $this->logger->error('NanoBanana generation failed', [
                'prompt' => $prompt,
                'error' => $e->getMessage(),
            ]);

            throw AiGenerationException::generationFailed('NanoBanana', $e->getMessage());
        }
    }

    public function getStatus(string $generationId): AiImageGenerationResult
    {
        if (!$this->isConfigured()) {
            throw AiGenerationException::providerNotConfigured('NanoBanana');
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . '/generations/' . $generationId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
            ]);

            $data = $response->toArray();

            return new AiImageGenerationResult(
                generationId: $generationId,
                status: $this->mapStatus($data['status'] ?? 'pending'),
                imageUrl: $data['image_url'] ?? $data['images'][0]['url'] ?? null,
                metadata: [
                    'provider' => 'nanobanana',
                    'raw_response' => $data,
                ]
            );

        } catch (\Exception $e) {
            $this->logger->error('NanoBanana status check failed', [
                'generation_id' => $generationId,
                'error' => $e->getMessage(),
            ]);

            throw AiGenerationException::generationFailed('NanoBanana', $e->getMessage());
        }
    }

    public function downloadImage(string $imageUrl, string $destination): string
    {
        try {
            $response = $this->httpClient->request('GET', $imageUrl);
            $content = $response->getContent();

            // Créer le répertoire si nécessaire
            $directory = dirname($destination);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Sauvegarder le fichier
            $result = file_put_contents($destination, $content);
            if ($result === false) {
                throw new FileException('Failed to save image to ' . $destination);
            }

            $this->logger->info('Image downloaded successfully', [
                'url' => $imageUrl,
                'destination' => $destination,
            ]);

            return $destination;

        } catch (\Exception $e) {
            $this->logger->error('Image download failed', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            throw AiGenerationException::downloadFailed($imageUrl, $e->getMessage());
        }
    }

    public function getProviderName(): string
    {
        return 'nanobanana';
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Mappe le statut NanoBanana vers notre statut standardisé
     */
    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending', 'processing', 'queued' => 'pending',
            'completed', 'success', 'succeeded' => 'completed',
            'failed', 'error' => 'failed',
            default => 'pending',
        };
    }
}
