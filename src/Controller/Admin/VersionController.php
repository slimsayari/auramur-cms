<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Product;
use App\Service\VersioningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class VersionController extends AbstractController
{
    public function __construct(
        private VersioningService $versioningService,
    ) {}

    #[Route('/products/{id}/versions', name: 'admin_product_versions', methods: ['GET'])]
    public function getProductVersions(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $history = $this->versioningService->getProductHistory($product);

        return $this->json([
            'productId' => $product->getId(),
            'totalVersions' => count($history),
            'versions' => $history,
        ]);
    }

    #[Route('/products/{id}/versions/{versionNumber}/rollback', name: 'admin_product_rollback', methods: ['POST'])]
    public function rollbackProduct(Product $product, int $versionNumber): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->versioningService->rollbackProduct($product, $versionNumber);

            return $this->json([
                'id' => $product->getId(),
                'message' => "Produit restauré à la version $versionNumber",
                'currentVersion' => $this->versioningService->getProductHistory($product)[0] ?? null,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/articles/{id}/versions', name: 'admin_article_versions', methods: ['GET'])]
    public function getArticleVersions(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $history = $this->versioningService->getArticleHistory($article);

        return $this->json([
            'articleId' => $article->getId(),
            'totalVersions' => count($history),
            'versions' => $history,
        ]);
    }

    #[Route('/articles/{id}/versions/{versionNumber}/rollback', name: 'admin_article_rollback', methods: ['POST'])]
    public function rollbackArticle(Article $article, int $versionNumber): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->versioningService->rollbackArticle($article, $versionNumber);

            return $this->json([
                'id' => $article->getId(),
                'message' => "Article restauré à la version $versionNumber",
                'currentVersion' => $this->versioningService->getArticleHistory($article)[0] ?? null,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
