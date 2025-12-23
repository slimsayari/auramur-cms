<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ArticleCreateDTO
{
    #[Assert\NotBlank(message: 'Le slug est obligatoire')]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Le slug doit être en minuscules avec des tirets')]
    public string $slug;

    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(min: 5, max: 255)]
    public string $title;

    #[Assert\NotBlank(message: 'Le contenu est obligatoire')]
    #[Assert\Length(min: 50)]
    public string $content;

    #[Assert\Length(max: 500)]
    public ?string $excerpt = null;

    #[Assert\Url]
    public ?string $featuredImageUrl = null;

    /** @var string[] */
    public array $categoryIds = [];

    /** @var string[] */
    public array $tagIds = [];
}
