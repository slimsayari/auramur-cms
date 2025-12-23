<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Product;
use App\Enum\ContentStatus;
use Doctrine\ORM\EntityManagerInterface;

class PublicationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TypesenseExporter $typesenseExporter,
    ) {}

    public function publishProduct(Product $product): void
    {
        // Vérifier les conditions
        if ($product->getImages()->isEmpty()) {
            throw new \InvalidArgumentException('Le produit doit avoir au moins une image');
        }

        if ($product->getVariants()->isEmpty()) {
            throw new \InvalidArgumentException('Le produit doit avoir au moins une variante');
        }

        if (!$product->getSeo()) {
            throw new \InvalidArgumentException('Le produit doit avoir des métadonnées SEO');
        }

        // Publier
        $product->setStatus(ContentStatus::PUBLISHED);
        $product->setPublishedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Exporter vers Typesense
        try {
            $this->typesenseExporter->exportProduct($product);
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer la publication
            error_log("Erreur lors de l'export Typesense: " . $e->getMessage());
        }
    }

    public function unpublishProduct(Product $product): void
    {
        $product->setStatus(ContentStatus::DRAFT);
        $product->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Supprimer de Typesense
        try {
            $this->typesenseExporter->deleteProduct($product);
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression Typesense: " . $e->getMessage());
        }
    }

    public function publishArticle(Article $article): void
    {
        if (!$article->getSeo()) {
            throw new \InvalidArgumentException('L\'article doit avoir des métadonnées SEO');
        }

        $article->setStatus(ContentStatus::PUBLISHED);
        $article->setPublishedAt(new \DateTimeImmutable());
        $article->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function unpublishArticle(Article $article): void
    {
        $article->setStatus(ContentStatus::DRAFT);
        $article->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function canPublishProduct(Product $product): array
    {
        $errors = [];

        if ($product->getImages()->isEmpty()) {
            $errors[] = 'Au moins une image est requise';
        }

        if ($product->getVariants()->isEmpty()) {
            $errors[] = 'Au moins une variante est requise';
        }

        if (!$product->getSeo()) {
            $errors[] = 'Les métadonnées SEO sont requises';
        }

        return $errors;
    }

    public function canPublishArticle(Article $article): array
    {
        $errors = [];

        if (!$article->getSeo()) {
            $errors[] = 'Les métadonnées SEO sont requises';
        }

        if (empty($article->getContent())) {
            $errors[] = 'Le contenu est requis';
        }

        return $errors;
    }
}
