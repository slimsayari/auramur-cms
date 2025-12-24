<?php

namespace App\Entity;

use App\Repository\ArticleSeoRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleSeoRepository::class)]
#[ORM\Table(name: 'article_seo')]
#[ORM\UniqueConstraint(columns: ['slug'], name: 'unique_article_slug')]
class ArticleSeo
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: Article::class, inversedBy: 'seo', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Article $article;

    #[ORM\Column(type: 'string', length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60, maxMessage: 'Le titre SEO ne peut pas dépasser {{ limit }} caractères.')]
    private string $seoTitle;

    #[ORM\Column(type: 'string', length: 160)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 160, maxMessage: 'La meta description ne peut pas dépasser {{ limit }} caractères.')]
    private string $metaDescription;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Le slug doit contenir uniquement des lettres minuscules, des chiffres et des tirets.')]
    private string $slug;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $canonicalUrl = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $noindex = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $nofollow = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $schemaReady = false;

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

    public function getArticle(): Article
    {
        return $this->article;
    }

    public function setArticle(Article $article): self
    {
        $this->article = $article;
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
