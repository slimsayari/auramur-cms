<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class WooProductImportDTO
{
    #[Assert\NotBlank]
    public string $wooId;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $title;

    #[Assert\NotBlank]
    public string $description;

    public ?string $shortDescription = null;

    #[Assert\NotBlank]
    public array $images = [];

    public array $categories = [];

    public array $tags = [];

    #[Assert\NotBlank]
    public array $variants = [];

    public ?string $seoTitle = null;

    public ?string $metaDescription = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $slug;
}
