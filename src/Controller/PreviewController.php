<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Product;
use App\Repository\ArticleRepository;
use App\Repository\ProductRepository;
use App\Repository\PreviewTokenRepository;
use App\Service\PreviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/preview')]
class PreviewController extends AbstractController
{
    public function __construct(
        private PreviewService $previewService,
        private PreviewTokenRepository $tokenRepository,
        private ProductRepository $productRepository,
        private ArticleRepository $articleRepository,
    ) {}

    #[Route('/{token}', name: 'preview_by_token', methods: ['GET'])]
    public function previewByToken(string $token): JsonResponse
    {
        $previewToken = $this->previewService->validateToken($token);

        if (!$previewToken) {
            return $this->json(['error' => 'Token invalide ou expiré'], 401);
        }

        if ($previewToken->getEntityType() === 'product') {
            $product = $this->productRepository->find($previewToken->getEntityId());
            if (!$product) {
                return $this->json(['error' => 'Produit non trouvé'], 404);
            }

            return $this->json([
                'type' => 'product',
                'data' => $this->formatProduct($product),
            ]);
        }

        if ($previewToken->getEntityType() === 'article') {
            $article = $this->articleRepository->find($previewToken->getEntityId());
            if (!$article) {
                return $this->json(['error' => 'Article non trouvé'], 404);
            }

            return $this->json([
                'type' => 'article',
                'data' => $this->formatArticle($article),
            ]);
        }

        return $this->json(['error' => 'Type d\'entité invalide'], 400);
    }

    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'slug' => $product->getSlug(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'status' => $product->getStatus()->value,
            'seo' => $product->getSeo() ? [
                'seoTitle' => $product->getSeo()->getSeoTitle(),
                'metaDescription' => $product->getSeo()->getMetaDescription(),
            ] : null,
        ];
    }

    private function formatArticle(Article $article): array
    {
        return [
            'id' => $article->getId(),
            'slug' => $article->getSlug(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'excerpt' => $article->getExcerpt(),
            'status' => $article->getStatus()->value,
            'seo' => $article->getSeo() ? [
                'seoTitle' => $article->getSeo()->getSeoTitle(),
                'metaDescription' => $article->getSeo()->getMetaDescription(),
            ] : null,
        ];
    }
}
