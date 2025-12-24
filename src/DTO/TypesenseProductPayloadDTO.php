<?php

namespace App\DTO;

class TypesenseProductPayloadDTO
{
    public function __construct()
    {
        $this->seoTitle = '';
        $this->metaDescription = '';
    }

    public string $id;
    public string $name;
    public string $description;
    public float $price;
    public array $variants = [];
    public array $categories = [];
    public array $tags = [];
    public array $images = [];
    public string $seoTitle;
    public string $metaDescription;
    public string $slug;
    public string $status = 'published';
    public \DateTimeImmutable $publishedAt;

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'variants' => $this->variants,
            'categories' => $this->categories,
            'tags' => $this->tags,
            'images' => $this->images,
            'seoTitle' => $this->seoTitle,
            'metaDescription' => $this->metaDescription,
            'slug' => $this->slug,
            'status' => $this->status,
            'publishedAt' => $this->publishedAt->format('Y-m-d H:i:s'),
        ];
    }
}
