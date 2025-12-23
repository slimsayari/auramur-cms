<?php

namespace App\Entity;

use App\Repository\ProductSeoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ProductSeoRepository::class)]
#[ORM\Table(name: 'product_seo')]
#[ORM\UniqueConstraint(columns: ['slug'], name: 'unique_product_slug')]
class ProductSeo
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: Product::class, inversedBy: 'seo', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(type: 'string', length: 60)]
    private string $seoTitle;

    #[ORM\Column(type: 'string', length: 160)]
    private string $metaDescription;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $canonicalUrl = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $noindex = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $nofollow = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $schemaReady = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $structuredData = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getSeoTitle(): string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(string $seoTitle): self
    {
        $this->seoTitle = $seoTitle;
        return $this;
    }

    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl;
    }

    public function setCanonicalUrl(?string $canonicalUrl): self
    {
        $this->canonicalUrl = $canonicalUrl;
        return $this;
    }

    public function isNoindex(): bool
    {
        return $this->noindex;
    }

    public function setNoindex(bool $noindex): self
    {
        $this->noindex = $noindex;
        return $this;
    }

    public function isNofollow(): bool
    {
        return $this->nofollow;
    }

    public function setNofollow(bool $nofollow): self
    {
        $this->nofollow = $nofollow;
        return $this;
    }

    public function isSchemaReady(): bool
    {
        return $this->schemaReady;
    }

    public function setSchemaReady(bool $schemaReady): self
    {
        $this->schemaReady = $schemaReady;
        return $this;
    }

    public function getStructuredData(): ?array
    {
        return $this->structuredData;
    }

    public function setStructuredData(?array $structuredData): self
    {
        $this->structuredData = $structuredData;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
