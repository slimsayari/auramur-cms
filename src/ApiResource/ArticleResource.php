<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Entity\Article;
use App\Enum\ContentStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    shortName: 'Article',
    operations: [
        new GetCollection(
            uriTemplate: '/articles',
            normalizationContext: ['groups' => ['article:read:collection']],
            paginationEnabled: true,
        ),
        new Get(
            uriTemplate: '/articles/{id}',
            normalizationContext: ['groups' => ['article:read']],
        ),
        new Post(
            uriTemplate: '/admin/articles',
            denormalizationContext: ['groups' => ['article:write']],
            normalizationContext: ['groups' => ['article:read']],
            security: 'is_granted("ROLE_ADMIN")',
        ),
        new Patch(
            uriTemplate: '/admin/articles/{id}',
            denormalizationContext: ['groups' => ['article:write']],
            normalizationContext: ['groups' => ['article:read']],
            security: 'is_granted("ROLE_ADMIN")',
        ),
    ],
)]
class ArticleResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['article:read'])]
    public Uuid $id;

    #[Groups(['article:read', 'article:write'])]
    public string $slug;

    #[Groups(['article:read', 'article:write'])]
    public string $title;

    #[Groups(['article:read', 'article:write'])]
    public string $content;

    #[Groups(['article:read', 'article:write'])]
    public ?string $excerpt = null;

    #[Groups(['article:read', 'article:write'])]
    public ?string $featuredImageUrl = null;

    #[Groups(['article:read'])]
    public ContentStatus $status;

    #[Groups(['article:read'])]
    public \DateTimeImmutable $createdAt;

    #[Groups(['article:read'])]
    public \DateTimeImmutable $updatedAt;

    #[Groups(['article:read'])]
    public ?\DateTimeImmutable $publishedAt = null;

    /** @var string[] */
    #[Groups(['article:read', 'article:write'])]
    public array $categories = [];

    /** @var string[] */
    #[Groups(['article:read', 'article:write'])]
    public array $tags = [];

    public static function fromEntity(Article $article): self
    {
        $resource = new self();
        $resource->id = $article->getId();
        $resource->slug = $article->getSlug();
        $resource->title = $article->getTitle();
        $resource->content = $article->getContent();
        $resource->excerpt = $article->getExcerpt();
        $resource->featuredImageUrl = $article->getFeaturedImageUrl();
        $resource->status = $article->getStatus();
        $resource->createdAt = $article->getCreatedAt();
        $resource->updatedAt = $article->getUpdatedAt();
        $resource->publishedAt = $article->getPublishedAt();

        $resource->categories = $article->getCategories()
            ->map(fn ($cat) => $cat->getSlug())
            ->toArray();

        $resource->tags = $article->getTags()
            ->map(fn ($tag) => $tag->getSlug())
            ->toArray();

        return $resource;
    }
}
