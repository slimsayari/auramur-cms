<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Tag;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Tag',
    operations: [
        new GetCollection(
            uriTemplate: '/tags',
            normalizationContext: ['groups' => ['tag:read']],
            paginationEnabled: true,
        ),
        new Get(
            uriTemplate: '/tags/{id}',
            normalizationContext: ['groups' => ['tag:read']],
        ),
    ],
)]
class TagResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['tag:read'])]
    public Uuid $id;

    #[Groups(['tag:read'])]
    public string $name;

    #[Groups(['tag:read'])]
    public string $slug;

    #[Groups(['tag:read'])]
    public \DateTimeImmutable $createdAt;

    public static function fromEntity(Tag $tag): self
    {
        $resource = new self();
        $resource->id = $tag->getId();
        $resource->name = $tag->getName();
        $resource->slug = $tag->getSlug();
        $resource->createdAt = $tag->getCreatedAt();

        return $resource;
    }
}
