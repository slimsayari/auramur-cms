<?php

namespace App\Entity;

use App\Repository\AiProviderConfigRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AiProviderConfigRepository::class)]
#[ORM\Table(name: 'ai_provider_configs')]
class AiProviderConfig
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 50)]
    private string $provider; // "nanobanana", "midjourney", "stable_diffusion"

    #[ORM\Column(type: 'text')]
    private string $apiKey; // Chiffré

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apiSecret = null; // Chiffré (optionnel)

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultPrompt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $imageSize = null;

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): self
    {
        $this->provider = $provider;
        return $this;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function getApiSecret(): ?string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(?string $apiSecret): self
    {
        $this->apiSecret = $apiSecret;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getDefaultPrompt(): ?string
    {
        return $this->defaultPrompt;
    }

    public function setDefaultPrompt(?string $defaultPrompt): self
    {
        $this->defaultPrompt = $defaultPrompt;
        return $this;
    }

    public function getImageSize(): ?string
    {
        return $this->imageSize;
    }

    public function setImageSize(?string $imageSize): self
    {
        $this->imageSize = $imageSize;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
