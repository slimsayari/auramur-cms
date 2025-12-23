<?php

namespace App\Service;

use App\Entity\AiGeneration;
use App\Entity\Article;
use App\Entity\Product;
use App\Enum\ContentStatus;
use Doctrine\ORM\EntityManagerInterface;

class ValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function validateAiGeneration(AiGeneration $aiGeneration): AiGeneration
    {
        if (ContentStatus::DRAFT !== $aiGeneration->getStatus()) {
            throw new \InvalidArgumentException('Seules les générations en brouillon peuvent être validées');
        }

        $aiGeneration->setStatus(ContentStatus::VALIDATED);
        $aiGeneration->setValidatedAt(new \DateTimeImmutable());

        // Appliquer le contenu généré au produit/article
        if ($product = $aiGeneration->getProduct()) {
            $this->applyAiGenerationToProduct($product, $aiGeneration);
        } elseif ($article = $aiGeneration->getArticle()) {
            $this->applyAiGenerationToArticle($article, $aiGeneration);
        }

        $this->entityManager->flush();

        return $aiGeneration;
    }

    public function rejectAiGeneration(AiGeneration $aiGeneration, string $reason): AiGeneration
    {
        if (ContentStatus::DRAFT !== $aiGeneration->getStatus()) {
            throw new \InvalidArgumentException('Seules les générations en brouillon peuvent être rejetées');
        }

        $aiGeneration->setStatus(ContentStatus::ARCHIVED);
        $aiGeneration->setRejectionReason($reason);

        $this->entityManager->flush();

        return $aiGeneration;
    }

    private function applyAiGenerationToProduct(Product $product, AiGeneration $aiGeneration): void
    {
        match ($aiGeneration->getType()) {
            \App\Enum\AiGenerationType::DESCRIPTION => $product->setDescription($aiGeneration->getGeneratedContent()),
            \App\Enum\AiGenerationType::TITLE => $product->setName($aiGeneration->getGeneratedContent()),
            \App\Enum\AiGenerationType::TAGS => $this->applyTagsToProduct($product, $aiGeneration->getGeneratedContent()),
            \App\Enum\AiGenerationType::SEO_META => $product->setMetadata(json_decode($aiGeneration->getGeneratedContent(), true)),
            default => null,
        };

        $product->setUpdatedAt(new \DateTimeImmutable());
    }

    private function applyAiGenerationToArticle(Article $article, AiGeneration $aiGeneration): void
    {
        match ($aiGeneration->getType()) {
            \App\Enum\AiGenerationType::DESCRIPTION => $article->setExcerpt($aiGeneration->getGeneratedContent()),
            \App\Enum\AiGenerationType::ARTICLE_CONTENT => $article->setContent($aiGeneration->getGeneratedContent()),
            \App\Enum\AiGenerationType::TITLE => $article->setTitle($aiGeneration->getGeneratedContent()),
            \App\Enum\AiGenerationType::TAGS => $this->applyTagsToArticle($article, $aiGeneration->getGeneratedContent()),
            default => null,
        };

        $article->setUpdatedAt(new \DateTimeImmutable());
    }

    private function applyTagsToProduct(Product $product, string $tagsJson): void
    {
        $tags = json_decode($tagsJson, true);
        if (!is_array($tags)) {
            return;
        }

        $product->getTags()->clear();
        foreach ($tags as $tagName) {
            // Créer ou récupérer le tag
            $slug = strtolower(str_replace(' ', '-', $tagName));
            $tag = $this->entityManager->getRepository(\App\Entity\Tag::class)->findOneBy(['slug' => $slug]);

            if (!$tag) {
                $tag = new \App\Entity\Tag();
                $tag->setName($tagName);
                $tag->setSlug($slug);
                $this->entityManager->persist($tag);
            }

            $product->addTag($tag);
        }
    }

    private function applyTagsToArticle(Article $article, string $tagsJson): void
    {
        $tags = json_decode($tagsJson, true);
        if (!is_array($tags)) {
            return;
        }

        $article->getTags()->clear();
        foreach ($tags as $tagName) {
            $slug = strtolower(str_replace(' ', '-', $tagName));
            $tag = $this->entityManager->getRepository(\App\Entity\Tag::class)->findOneBy(['slug' => $slug]);

            if (!$tag) {
                $tag = new \App\Entity\Tag();
                $tag->setName($tagName);
                $tag->setSlug($slug);
                $this->entityManager->persist($tag);
            }

            $article->addTag($tag);
        }
    }
}
