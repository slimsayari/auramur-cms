<?php

namespace App\DTO;

/**
 * DTO représentant le résultat d'une génération d'image IA
 */
class AiImageGenerationResult
{
    public function __construct(
        public string $generationId,
        public string $status,           // "pending", "completed", "failed"
        public ?string $imageUrl = null,
        public ?string $error = null,
        public array $metadata = []
    ) {
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function toArray(): array
    {
        return [
            'generation_id' => $this->generationId,
            'status' => $this->status,
            'image_url' => $this->imageUrl,
            'error' => $this->error,
            'metadata' => $this->metadata,
        ];
    }
}
