<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ProductCreateDTO
{
    #[Assert\NotBlank(message: 'Le slug est obligatoire')]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Le slug doit être en minuscules avec des tirets')]
    public string $slug;

    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 3, max: 255)]
    public string $name;

    #[Assert\Length(max: 5000)]
    public ?string $description = null;

    #[Assert\Length(max: 50)]
    public ?string $sku = null;

    #[Assert\Regex(pattern: '/^\d+(\.\d{2})?$/', message: 'Le prix doit être au format décimal')]
    public ?string $price = null;

    /** @var string[] */
    public array $categoryIds = [];

    /** @var string[] */
    public array $tagIds = [];

    /** @var array<array{url: string, format?: string, dpi?: int, width?: int, height?: int, altText?: string}> */
    public array $images = [];
}
