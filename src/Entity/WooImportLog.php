<?php

namespace App\Entity;

use App\Repository\WooImportLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WooImportLogRepository::class)]
#[ORM\Table(name: 'woo_import_logs')]
#[ORM\Index(columns: ['status', 'imported_at'], name: 'idx_import_status_date')]
class WooImportLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $productsImported = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $variantsImported = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $imagesImported = 0;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $errors = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $importedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->importedAt = new \DateTimeImmutable();
        $this->status = 'pending';
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getProductsImported(): int
    {
        return $this->productsImported;
    }

    public function setProductsImported(int $productsImported): self
    {
        $this->productsImported = $productsImported;
        return $this;
    }

    public function getVariantsImported(): int
    {
        return $this->variantsImported;
    }

    public function setVariantsImported(int $variantsImported): self
    {
        $this->variantsImported = $variantsImported;
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

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(?array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function addError(string $error): self
    {
        if ($this->errors === null) {
            $this->errors = [];
        }
        $this->errors[] = $error;
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

    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        if ($this->completedAt === null) {
            return null;
        }
        return $this->completedAt->getTimestamp() - $this->importedAt->getTimestamp();
    }
}
