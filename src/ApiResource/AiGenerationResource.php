<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use App\Entity\AiGeneration;
use App\Enum\AiGenerationType;
use App\Enum\ContentStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'AiGeneration',
    operations: [
        new GetCollection(
            uriTemplate: '/admin/ai-generations',
            normalizationContext: ['groups' => ['ai_generation:read']],
            security: 'is_granted("ROLE_ADMIN")',
            paginationEnabled: true,
        ),
        new Get(
            uriTemplate: '/admin/ai-generations/{id}',
            normalizationContext: ['groups' => ['ai_generation:read']],
            security: 'is_granted("ROLE_ADMIN")',
        ),
        new Patch(
            uriTemplate: '/admin/ai-generations/{id}',
            denormalizationContext: ['groups' => ['ai_generation:write']],
            normalizationContext: ['groups' => ['ai_generation:read']],
            security: 'is_granted("ROLE_ADMIN")',
        ),
    ],
)]
class AiGenerationResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['ai_generation:read'])]
    public Uuid $id;

    #[Groups(['ai_generation:read'])]
    public ?Uuid $productId = null;

    #[Groups(['ai_generation:read'])]
    public ?Uuid $articleId = null;

    #[Groups(['ai_generation:read'])]
    public AiGenerationType $type;

    #[Groups(['ai_generation:read'])]
    public string $generatedContent;

    #[Groups(['ai_generation:read', 'ai_generation:write'])]
    public ContentStatus $status;

    #[Groups(['ai_generation:read'])]
    public ?string $rejectionReason = null;

    #[Groups(['ai_generation:read', 'ai_generation:write'])]
    public ?\DateTimeImmutable $validatedAt = null;

    #[Groups(['ai_generation:read'])]
    public \DateTimeImmutable $generatedAt;

    #[Groups(['ai_generation:read'])]
    public \DateTimeImmutable $createdAt;

    public static function fromEntity(AiGeneration $aiGeneration): self
    {
        $resource = new self();
        $resource->id = $aiGeneration->getId();
        $resource->productId = $aiGeneration->getProduct()?->getId();
        $resource->articleId = $aiGeneration->getArticle()?->getId();
        $resource->type = $aiGeneration->getType();
        $resource->generatedContent = $aiGeneration->getGeneratedContent();
        $resource->status = $aiGeneration->getStatus();
        $resource->rejectionReason = $aiGeneration->getRejectionReason();
        $resource->validatedAt = $aiGeneration->getValidatedAt();
        $resource->generatedAt = $aiGeneration->getGeneratedAt();
        $resource->createdAt = $aiGeneration->getCreatedAt();

        return $resource;
    }
}
