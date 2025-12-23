<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ContentVersion;
use App\Entity\Product;
use App\Repository\ContentVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class VersioningService
{
    private const TRACKED_PRODUCT_FIELDS = [
        'name', 'description', 'price', 'status', 'sku',
    ];

    private const TRACKED_ARTICLE_FIELDS = [
        'title', 'content', 'excerpt', 'status',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContentVersionRepository $versionRepository,
    ) {}

    public function createProductVersion(
        Product $product,
        ?Uuid $changedBy = null,
        ?string $reason = null
    ): ContentVersion {
        $snapshot = $this->buildProductSnapshot($product);
        $versionNumber = $this->versionRepository->getNextVersionNumber('product', $product->getId());

        $version = new ContentVersion();
        $version->setEntityType('product');
        $version->setEntityId($product->getId());
        $version->setVersionNumber($versionNumber);
        $version->setSnapshot($snapshot);
        $version->setChangedBy($changedBy);
        $version->setChangeReason($reason);

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return $version;
    }

    public function createArticleVersion(
        Article $article,
        ?Uuid $changedBy = null,
        ?string $reason = null
    ): ContentVersion {
        $snapshot = $this->buildArticleSnapshot($article);
        $versionNumber = $this->versionRepository->getNextVersionNumber('article', $article->getId());

        $version = new ContentVersion();
        $version->setEntityType('article');
        $version->setEntityId($article->getId());
        $version->setVersionNumber($versionNumber);
        $version->setSnapshot($snapshot);
        $version->setChangedBy($changedBy);
        $version->setChangeReason($reason);

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        return $version;
    }

    public function rollbackProduct(Product $product, int $versionNumber): void
    {
        $versions = $this->versionRepository->findByEntity('product', $product->getId());
        $targetVersion = null;

        foreach ($versions as $v) {
            if ($v->getVersionNumber() === $versionNumber) {
                $targetVersion = $v;
                break;
            }
        }

        if (!$targetVersion) {
            throw new \InvalidArgumentException("Version $versionNumber non trouvée pour ce produit.");
        }

        $snapshot = $targetVersion->getSnapshot();
        foreach (self::TRACKED_PRODUCT_FIELDS as $field) {
            if (isset($snapshot[$field])) {
                $setter = 'set' . ucfirst($field);
                $product->$setter($snapshot[$field]);
            }
        }

        $product->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Créer une version de rollback
        $this->createProductVersion(
            $product,
            null,
            "Rollback vers version $versionNumber"
        );
    }

    public function rollbackArticle(Article $article, int $versionNumber): void
    {
        $versions = $this->versionRepository->findByEntity('article', $article->getId());
        $targetVersion = null;

        foreach ($versions as $v) {
            if ($v->getVersionNumber() === $versionNumber) {
                $targetVersion = $v;
                break;
            }
        }

        if (!$targetVersion) {
            throw new \InvalidArgumentException("Version $versionNumber non trouvée pour cet article.");
        }

        $snapshot = $targetVersion->getSnapshot();
        foreach (self::TRACKED_ARTICLE_FIELDS as $field) {
            if (isset($snapshot[$field])) {
                $setter = 'set' . ucfirst($field);
                $article->$setter($snapshot[$field]);
            }
        }

        $article->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Créer une version de rollback
        $this->createArticleVersion(
            $article,
            null,
            "Rollback vers version $versionNumber"
        );
    }

    public function getProductHistory(Product $product): array
    {
        $versions = $this->versionRepository->findByEntity('product', $product->getId());

        return array_map(fn (ContentVersion $v) => [
            'versionNumber' => $v->getVersionNumber(),
            'snapshot' => $v->getSnapshot(),
            'changedBy' => $v->getChangedBy(),
            'createdAt' => $v->getCreatedAt(),
            'changeReason' => $v->getChangeReason(),
        ], $versions);
    }

    public function getArticleHistory(Article $article): array
    {
        $versions = $this->versionRepository->findByEntity('article', $article->getId());

        return array_map(fn (ContentVersion $v) => [
            'versionNumber' => $v->getVersionNumber(),
            'snapshot' => $v->getSnapshot(),
            'changedBy' => $v->getChangedBy(),
            'createdAt' => $v->getCreatedAt(),
            'changeReason' => $v->getChangeReason(),
        ], $versions);
    }

    private function buildProductSnapshot(Product $product): array
    {
        return [
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'status' => $product->getStatus()->value,
            'sku' => $product->getSku(),
        ];
    }

    private function buildArticleSnapshot(Article $article): array
    {
        return [
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'excerpt' => $article->getExcerpt(),
            'status' => $article->getStatus()->value,
        ];
    }
}
