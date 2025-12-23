<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Category;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Category',
    operations: [
        new GetCollection(
            uriTemplate: '/categories',
            normalizationContext: ['groups' => ['category:read']],
            paginationEnabled: true,
        ),
        new Get(
            uriTemplate: '/categories/{id}',
            normalizationContext: ['groups' => ['category:read']],
        ),
    ],
)]
class CategoryResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['category:read'])]
    public Uuid $id;

    #[Groups(['category:read'])]
    public string $slug;

    #[Groups(['category:read'])]
    public string $name;

    #[Groups(['category:read'])]
    public ?string $description = null;

    #[Groups(['category:read'])]
    public \DateTimeImmutable $createdAt;

    public static function fromEntity(Category $category): self
    {
        $resource = new self();
        $resource->id = $category->getId();
        $resource->slug = $category->getSlug();
        $resource->name = $category->getName();
        $resource->description = $category->getDescription();
        $resource->createdAt = $category->getCreatedAt();

        return $resource;
    }
}
