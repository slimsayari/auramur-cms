<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class WordpressArticleImportDTO
{
    #[Assert\NotBlank]
    public string $title;

    #[Assert\NotBlank]
    public string $content;

    public ?string $excerpt = null;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Le slug doit contenir uniquement des lettres minuscules, chiffres et tirets')]
    public string $slug;

    public ?string $publishedAt = null;

    public ?string $author = null;

    public ?string $featuredImage = null;

    public array $categories = [];

    public array $tags = [];

    public array $seo = [];

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->title = $data['title'] ?? '';
        $dto->content = $data['content'] ?? '';
        $dto->excerpt = $data['excerpt'] ?? null;
        $dto->slug = $data['slug'] ?? '';
        $dto->publishedAt = $data['published_at'] ?? $data['date'] ?? null;
        $dto->author = $data['author'] ?? null;
        $dto->featuredImage = $data['featured_image'] ?? $data['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null;
        $dto->categories = $data['categories'] ?? [];
        $dto->tags = $data['tags'] ?? [];
        $dto->seo = $data['seo'] ?? $data['yoast_head_json'] ?? [];

        return $dto;
    }
}
