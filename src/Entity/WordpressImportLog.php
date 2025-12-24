<?php

namespace App\Entity;

use App\Repository\WordpressImportLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WordpressImportLogRepository::class)]
#[ORM\Table(name: 'wordpress_import_logs')]
class WordpressImportLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 50)]
    private string $source; // "rest_api", "json", "xml"

    #[ORM\Column(length: 50)]
    private string $status; // "processing", "success", "failed"

    #[ORM\Column]
    private int $articlesImported = 0;

    #[ORM\Column]
    private int $imagesImported = 0;

    #[ORM\Column]
    private int $categoriesImported = 0;

    #[ORM\Column]
    private int $tagsImported = 0;

    #[ORM\Column(type: 'json')]
    private array $errors = [];

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    #[ORM\Column]
    private \DateTimeImmutable $importedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->importedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getArticlesImported(): int
    {
        return $this->articlesImported;
    }

    public function setArticlesImported(int $articlesImported): self
    {
        $this->articlesImported = $articlesImported;
        return $this;
    }

    public function incrementArticlesImported(): self
    {
        $this->articlesImported++;
        return $this;
    }

    public function getImagesImported(): int
    {
        return $this->imagesImported;
    }

    public function setImagesImported(int $imagesImported): self
    {
        $this->imagesImported = $imagesImported;
        return $this;
    }

    public function incrementImagesImported(): self
    {
        $this->imagesImported++;
        return $this;
    }

    public function getCategoriesImported(): int
    {
        return $this->categoriesImported;
    }

    public function setCategoriesImported(int $categoriesImported): self
    {
        $this->categoriesImported = $categoriesImported;
        return $this;
    }

    public function incrementCategoriesImported(): self
    {
        $this->categoriesImported++;
        return $this;
    }

    public function getTagsImported(): int
    {
        return $this->tagsImported;
    }

    public function setTagsImported(int $tagsImported): self
    {
        $this->tagsImported = $tagsImported;
        return $this;
    }

    public function incrementTagsImported(): self
    {
        $this->tagsImported++;
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }
}
