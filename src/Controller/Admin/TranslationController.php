<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Product;
use App\Service\TranslationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class TranslationController extends AbstractController
{
    public function __construct(
        private TranslationService $translationService,
    ) {}

    #[Route('/products/{id}/translations/{locale}', name: 'admin_product_translations', methods: ['GET'])]
    public function getProductTranslations(Product $product, string $locale): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $translations = $this->translationService->getTranslations('product', $product->getId(), $locale);

        return $this->json([
            'productId' => $product->getId(),
            'locale' => $locale,
            'translations' => $translations,
        ]);
    }

    #[Route('/products/{id}/translations/{locale}', name: 'admin_product_translations_update', methods: ['POST', 'PATCH'])]
    public function updateProductTranslations(Product $product, string $locale, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            $this->translationService->setMultipleTranslations(
                'product',
                $product->getId(),
                $data,
                $locale
            );

            return $this->json([
                'productId' => $product->getId(),
                'locale' => $locale,
                'message' => 'Traductions mises à jour',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/products/{id}/translations', name: 'admin_product_available_locales', methods: ['GET'])]
    public function getProductAvailableLocales(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $locales = $this->translationService->getAvailableLocales('product', $product->getId());

        return $this->json([
            'productId' => $product->getId(),
            'availableLocales' => $locales,
        ]);
    }

    #[Route('/articles/{id}/translations/{locale}', name: 'admin_article_translations', methods: ['GET'])]
    public function getArticleTranslations(Article $article, string $locale): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $translations = $this->translationService->getTranslations('article', $article->getId(), $locale);

        return $this->json([
            'articleId' => $article->getId(),
            'locale' => $locale,
            'translations' => $translations,
        ]);
    }

    #[Route('/articles/{id}/translations/{locale}', name: 'admin_article_translations_update', methods: ['POST', 'PATCH'])]
    public function updateArticleTranslations(Article $article, string $locale, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            $this->translationService->setMultipleTranslations(
                'article',
                $article->getId(),
                $data,
                $locale
            );

            return $this->json([
                'articleId' => $article->getId(),
                'locale' => $locale,
                'message' => 'Traductions mises à jour',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/articles/{id}/translations', name: 'admin_article_available_locales', methods: ['GET'])]
    public function getArticleAvailableLocales(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $locales = $this->translationService->getAvailableLocales('article', $article->getId());

        return $this->json([
            'articleId' => $article->getId(),
            'availableLocales' => $locales,
        ]);
    }
}
