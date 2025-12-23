<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use App\Entity\ProductImage;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

class ProductImageResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['product:read'])]
    public Uuid $id;

    #[Groups(['product:read'])]
    public string $url;

    #[Groups(['product:read'])]
    public ?string $format = null;

    #[Groups(['product:read'])]
    public ?int $dpi = null;

    #[Groups(['product:read'])]
    public ?int $width = null;

    #[Groups(['product:read'])]
    public ?int $height = null;

    #[Groups(['product:read'])]
    public int $position = 0;

    #[Groups(['product:read'])]
    public bool $isThumbnail = false;

    #[Groups(['product:read'])]
    public ?string $altText = null;

    public static function fromEntity(ProductImage $image): self
    {
        $resource = new self();
        $resource->id = $image->getId();
        $resource->url = $image->getUrl();
        $resource->format = $image->getFormat();
        $resource->dpi = $image->getDpi();
        $resource->width = $image->getWidth();
        $resource->height = $image->getHeight();
        $resource->position = $image->getPosition();
        $resource->isThumbnail = $image->isThumbnail();
        $resource->altText = $image->getAltText();

        return $resource;
    }
}
