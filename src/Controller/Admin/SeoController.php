<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Product;
use App\Service\SeoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class SeoController extends AbstractController
{
    public function __construct(
        private SeoService $seoService,
    ) {}

    #[Route('/products/{id}/seo', name: 'admin_product_seo', methods: ['GET'])]
    public function getProductSeo(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $seo = $product->getSeo();
        if (!$seo) {
            return $this->json(['error' => 'SEO non configuré'], 404);
        }

        return $this->json([
            'id' => $seo->getId(),
            'seoTitle' => $seo->getSeoTitle(),
            'metaDescription' => $seo->getMetaDescription(),
            'slug' => $seo->getSlug(),
            'canonicalUrl' => $seo->getCanonicalUrl(),
            'noindex' => $seo->isNoindex(),
            'nofollow' => $seo->isNofollow(),
            'schemaReady' => $seo->isSchemaReady(),
            'structuredData' => $seo->getStructuredData(),
        ]);
    }

    #[Route('/products/{id}/seo', name: 'admin_product_seo_update', methods: ['POST', 'PATCH'])]
    public function updateProductSeo(Product $product, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $seo = $this->seoService->createOrUpdateProductSeo($product, $data);

            return $this->json([
                'id' => $seo->getId(),
                'seoTitle' => $seo->getSeoTitle(),
                'metaDescription' => $seo->getMetaDescription(),
                'slug' => $seo->getSlug(),
                'message' => 'SEO produit mis à jour',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/articles/{id}/seo', name: 'admin_article_seo', methods: ['GET'])]
    public function getArticleSeo(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $seo = $article->getSeo();
        if (!$seo) {
            return $this->json(['error' => 'SEO non configuré'], 404);
        }

        return $this->json([
            'id' => $seo->getId(),
            'seoTitle' => $seo->getSeoTitle(),
            'metaDescription' => $seo->getMetaDescription(),
            'slug' => $seo->getSlug(),
            'canonicalUrl' => $seo->getCanonicalUrl(),
            'noindex' => $seo->isNoindex(),
            'nofollow' => $seo->isNofollow(),
            'schemaReady' => $seo->isSchemaReady(),
        ]);
    }

    #[Route('/articles/{id}/seo', name: 'admin_article_seo_update', methods: ['POST', 'PATCH'])]
    public function updateArticleSeo(Article $article, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $seo = $this->seoService->createOrUpdateArticleSeo($article, $data);

            return $this->json([
                'id' => $seo->getId(),
                'seoTitle' => $seo->getSeoTitle(),
                'metaDescription' => $seo->getMetaDescription(),
                'slug' => $seo->getSlug(),
                'message' => 'SEO article mis à jour',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/categories/{id}/seo', name: 'admin_category_seo', methods: ['GET'])]
    public function getCategorySeo(Category $category): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $seo = $category->getSeo();
        if (!$seo) {
            return $this->json(['error' => 'SEO non configuré'], 404);
        }

        return $this->json([
            'id' => $seo->getId(),
            'seoTitle' => $seo->getSeoTitle(),
            'metaDescription' => $seo->getMetaDescription(),
            'slug' => $seo->getSlug(),
            'canonicalUrl' => $seo->getCanonicalUrl(),
            'noindex' => $seo->isNoindex(),
            'nofollow' => $seo->isNofollow(),
        ]);
    }

    #[Route('/categories/{id}/seo', name: 'admin_category_seo_update', methods: ['POST', 'PATCH'])]
    public function updateCategorySeo(Category $category, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $seo = $this->seoService->createOrUpdateCategorySeo($category, $data);

            return $this->json([
                'id' => $seo->getId(),
                'seoTitle' => $seo->getSeoTitle(),
                'metaDescription' => $seo->getMetaDescription(),
                'slug' => $seo->getSlug(),
                'message' => 'SEO catégorie mis à jour',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
