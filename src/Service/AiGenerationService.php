<?php

namespace App\Service;

use App\DTO\AiGenerationWebhookDTO;
use App\Entity\AiGeneration;
use App\Entity\Article;
use App\Entity\Product;
use App\Enum\AiGenerationType;
use App\Enum\ContentStatus;
use App\Repository\ArticleRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class AiGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private ArticleRepository $articleRepository,
    ) {}

    public function processWebhookGeneration(AiGenerationWebhookDTO $dto): AiGeneration
    {
        $aiGeneration = new AiGeneration();
        $aiGeneration->setType(AiGenerationType::from($dto->type));
        $aiGeneration->setGeneratedContent($dto->content);
        $aiGeneration->setMetadata($dto->metadata);
        $aiGeneration->setStatus(ContentStatus::DRAFT);

        // Associer au produit ou article
        if ($dto->productId) {
            $product = $this->productRepository->find($dto->productId);
            if (!$product) {
                throw new \InvalidArgumentException(sprintf('Produit %s non trouvé', $dto->productId));
            }
            $aiGeneration->setProduct($product);
        }

        if ($dto->articleId) {
            $article = $this->articleRepository->find($dto->articleId);
            if (!$article) {
                throw new \InvalidArgumentException(sprintf('Article %s non trouvé', $dto->articleId));
            }
            $aiGeneration->setArticle($article);
        }

        $this->entityManager->persist($aiGeneration);
        $this->entityManager->flush();

        return $aiGeneration;
    }

    public function getPendingGenerations(): array
    {
        return $this->entityManager
            ->getRepository(AiGeneration::class)
            ->findPendingValidation();
    }

    public function getGenerationsByStatus(ContentStatus $status): array
    {
        return $this->entityManager
            ->getRepository(AiGeneration::class)
            ->findByStatus($status);
    }
}
