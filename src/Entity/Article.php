<?php

namespace App\Entity;

use App\Enum\ContentStatus;
use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'articles')]
#[ORM\Index(columns: ['status', 'created_at'], name: 'idx_article_status_created')]
#[ORM\Index(columns: ['slug'], name: 'idx_article_slug')]
class Article
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $featuredImageUrl = null;

    #[ORM\Column(type: 'string', enumType: ContentStatus::class)]
    private ContentStatus $status = ContentStatus::DRAFT;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'articles', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'article_categories')]
    private Collection $categories;

    /** @var Collection<int, Tag> */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'articles', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'article_tags')]
    private Collection $tags;

    /** @var Collection<int, AiGeneration> */
    #[ORM\OneToMany(targetEntity: AiGeneration::class, mappedBy: 'article', cascade: ['persist', 'remove'])]
    private Collection $aiGenerations;

    #[ORM\OneToOne(targetEntity: ArticleSeo::class, mappedBy: 'article', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ArticleSeo $seo = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->aiGenerations = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): self
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getFeaturedImageUrl(): ?string
    {
        return $this->featuredImageUrl;
    }

    public function setFeaturedImageUrl(?string $featuredImageUrl): self
    {
        $this->featuredImageUrl = $featuredImageUrl;
        return $this;
    }

    public function getStatus(): ContentStatus
    {
        return $this->status;
    }

    public function setStatus(ContentStatus $status): self
    {
        $this->status = $status;
        if (ContentStatus::PUBLISHED === $status && null === $this->publishedAt) {
            $this->publishedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): self
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    /** @return Collection<int, Category> */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }
        return $this;
    }

    public function removeCategory(Category $category): self
    {
        $this->categories->removeElement($category);
        return $this;
    }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    /** @return Collection<int, AiGeneration> */
    public function getAiGenerations(): Collection
    {
        return $this->aiGenerations;
    }

    public function addAiGeneration(AiGeneration $aiGeneration): self
    {
        if (!$this->aiGenerations->contains($aiGeneration)) {
            $this->aiGenerations->add($aiGeneration);
            $aiGeneration->setArticle($this);
        }
        return $this;
    }

    public function removeAiGeneration(AiGeneration $aiGeneration): self
    {
        if ($this->aiGenerations->removeElement($aiGeneration)) {
            if ($aiGeneration->getArticle() === $this) {
                $aiGeneration->setArticle(null);
            }
        }
        return $this;
    }

    public function getSeo(): ?ArticleSeo
    {
        return $this->seo;
    }

    public function setSeo(?ArticleSeo $seo): self
    {
        $this->seo = $seo;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): self
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }
}
