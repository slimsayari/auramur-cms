<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleSeo;
use App\Entity\Category;
use App\Entity\CategorySeo;
use App\Entity\Product;
use App\Entity\ProductSeo;
use Doctrine\ORM\EntityManagerInterface;

class SeoService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function generateSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    public function generateStructuredData(Product $product): array
    {
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'image' => $product->getImages()->first()?->getUrl() ?? null,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Auramur',
            ],
            'offers' => [
                '@type' => 'AggregateOffer',
                'priceCurrency' => 'EUR',
                'lowPrice' => $this->getLowestVariantPrice($product),
                'highPrice' => $this->getHighestVariantPrice($product),
                'offerCount' => count($product->getVariants()),
            ],
        ];
    }

    public function createOrUpdateProductSeo(Product $product, array $data): ProductSeo
    {
        $seo = $product->getSeo() ?? new ProductSeo();

        $seo->setProduct($product);
        $seo->setSeoTitle($data['seoTitle'] ?? $product->getName());
        $seo->setMetaDescription($data['metaDescription'] ?? substr($product->getDescription(), 0, 160));
        $seo->setSlug($data['slug'] ?? $this->generateSlug($product->getName()));
        $seo->setCanonicalUrl($data['canonicalUrl'] ?? null);
        $seo->setNoindex($data['noindex'] ?? false);
        $seo->setNofollow($data['nofollow'] ?? false);
        $seo->setSchemaReady($data['schemaReady'] ?? false);

        if ($data['schemaReady'] ?? false) {
            $seo->setStructuredData($this->generateStructuredData($product));
        }

        $seo->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($seo);

        return $seo;
    }

    public function createOrUpdateArticleSeo(Article $article, array $data): ArticleSeo
    {
        $seo = $article->getSeo() ?? new ArticleSeo();

        $seo->setArticle($article);
        $seo->setSeoTitle($data['seoTitle'] ?? $article->getTitle());
        $seo->setMetaDescription($data['metaDescription'] ?? substr($article->getExcerpt() ?? '', 0, 160));
        $seo->setSlug($data['slug'] ?? $this->generateSlug($article->getTitle()));
        $seo->setCanonicalUrl($data['canonicalUrl'] ?? null);
        $seo->setNoindex($data['noindex'] ?? false);
        $seo->setNofollow($data['nofollow'] ?? false);
        $seo->setSchemaReady($data['schemaReady'] ?? false);

        $seo->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($seo);

        return $seo;
    }

    public function createOrUpdateCategorySeo(Category $category, array $data): CategorySeo
    {
        $seo = $category->getSeo() ?? new CategorySeo();

        $seo->setCategory($category);
        $seo->setSeoTitle($data['seoTitle'] ?? $category->getName());
        $seo->setMetaDescription($data['metaDescription'] ?? substr($category->getDescription() ?? '', 0, 160));
        $seo->setSlug($data['slug'] ?? $this->generateSlug($category->getName()));
        $seo->setCanonicalUrl($data['canonicalUrl'] ?? null);
        $seo->setNoindex($data['noindex'] ?? false);
        $seo->setNofollow($data['nofollow'] ?? false);

        $seo->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($seo);

        return $seo;
    }

    private function getLowestVariantPrice(Product $product): ?float
    {
        $variants = $product->getVariants();
        if ($variants->isEmpty()) {
            return null;
        }

        $prices = $variants->map(fn ($v) => (float) $v->getPricePerM2())->toArray();
        return min($prices);
    }

    private function getHighestVariantPrice(Product $product): ?float
    {
        $variants = $product->getVariants();
        if ($variants->isEmpty()) {
            return null;
        }

        $prices = $variants->map(fn ($v) => (float) $v->getPricePerM2())->toArray();
        return max($prices);
    }
}
