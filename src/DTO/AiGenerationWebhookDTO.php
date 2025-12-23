<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AiGenerationWebhookDTO
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $productId;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['description', 'title', 'tags', 'seo_meta'])]
    public string $type;

    #[Assert\NotBlank]
    #[Assert\Length(min: 10)]
    public string $content;

    /** @var array<string, mixed> */
    public array $metadata = [];

    public ?string $articleId = null;
}
