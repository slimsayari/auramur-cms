<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Product;
use App\Enum\ContentStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Product',
    operations: [
        new GetCollection(
            uriTemplate: '/products',
            normalizationContext: ['groups' => ['product:read:collection']],
            paginationEnabled: true,
        ),
        new Get(
            uriTemplate: '/products/{id}',
            normalizationContext: ['groups' => ['product:read']],
        ),
        new Post(
            uriTemplate: '/admin/products',
            denormalizationContext: ['groups' => ['product:write']],
            normalizationContext: ['groups' => ['product:read']],
            security: 'is_granted("ROLE_ADMIN")',
        ),
        new Patch(
            uriTemplate: '/admin/products/{id}',
            denormalizationContext: ['groups' => ['product:write']],
            normalizationContext: ['groups' => ['product:read']],
            security: 'is_granted("ROLE_ADMIN")',
        ),
    ],
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']],
)]
class ProductResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['product:read'])]
    public Uuid $id;

    #[Groups(['product:read', 'product:write'])]
    public string $slug;

    #[Groups(['product:read', 'product:write'])]
    public string $name;

    #[Groups(['product:read', 'product:write'])]
    public ?string $description = null;

    #[Groups(['product:read', 'product:write'])]
    public ?string $sku = null;

    #[Groups(['product:read', 'product:write'])]
    public ?string $price = null;

    #[Groups(['product:read'])]
    public ContentStatus $status;

    #[Groups(['product:read'])]
    public \DateTimeImmutable $createdAt;

    #[Groups(['product:read'])]
    public \DateTimeImmutable $updatedAt;

    #[Groups(['product:read'])]
    public ?\DateTimeImmutable $publishedAt = null;

    /** @var ProductImageResource[] */
    #[Groups(['product:read'])]
    public array $images = [];

    /** @var string[] */
    #[Groups(['product:read', 'product:write'])]
    public array $categories = [];

    /** @var string[] */
    #[Groups(['product:read', 'product:write'])]
    public array $tags = [];

    public static function fromEntity(Product $product): self
    {
        $resource = new self();
        $resource->id = $product->getId();
        $resource->slug = $product->getSlug();
        $resource->name = $product->getName();
        $resource->description = $product->getDescription();
        $resource->sku = $product->getSku();
        $resource->price = $product->getPrice();
        $resource->status = $product->getStatus();
        $resource->createdAt = $product->getCreatedAt();
        $resource->updatedAt = $product->getUpdatedAt();
        $resource->publishedAt = $product->getPublishedAt();

        $resource->images = $product->getImages()
            ->map(fn ($img) => ProductImageResource::fromEntity($img))
            ->toArray();

        $resource->categories = $product->getCategories()
            ->map(fn ($cat) => $cat->getSlug())
            ->toArray();

        $resource->tags = $product->getTags()
            ->map(fn ($tag) => $tag->getSlug())
            ->toArray();

        return $resource;
    }
}
