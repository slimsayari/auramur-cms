<?php

namespace App\Entity;

use App\Enum\AiGenerationType;
use App\Enum\ContentStatus;
use App\Repository\AiGenerationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AiGenerationRepository::class)]
#[ORM\Table(name: 'ai_generations')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_ai_status_created')]
class AiGeneration
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'aiGenerations')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'aiGenerations')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Article $article = null;

    #[ORM\Column(type: 'string', enumType: AiGenerationType::class)]
    private AiGenerationType $type;

    #[ORM\Column(type: 'text')]
    private string $generatedContent;

    #[ORM\Column(type: 'string', enumType: ContentStatus::class)]
    private ContentStatus $status = ContentStatus::DRAFT;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $generatedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $prompt = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $validatedBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationNotes = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->generatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function getType(): AiGenerationType
    {
        return $this->type;
    }

    public function setType(AiGenerationType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getGeneratedContent(): string
    {
        return $this->generatedContent;
    }

    public function setGeneratedContent(string $generatedContent): self
    {
        $this->generatedContent = $generatedContent;
        return $this;
    }

    public function getStatus(): ContentStatus
    {
        return $this->status;
    }

    public function setStatus(ContentStatus $status): self
    {
        $this->status = $status;
        if (ContentStatus::VALIDATED === $status && null === $this->validatedAt) {
            $this->validatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function setGeneratedAt(\DateTimeImmutable $generatedAt): self
    {
        $this->generatedAt = $generatedAt;
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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): self
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getValidatedBy(): ?Uuid
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?Uuid $validatedBy): self
    {
        $this->validatedBy = $validatedBy;
        return $this;
    }

    public function getValidationNotes(): ?string
    {
        return $this->validationNotes;
    }

    public function setValidationNotes(?string $validationNotes): self
    {
        $this->validationNotes = $validationNotes;
        return $this;
    }
}
