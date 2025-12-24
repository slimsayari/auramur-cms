<?php

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Entity\ProductSeo;
use App\Entity\Article;
use App\Entity\ArticleSeo;
use App\Service\SeoService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SeoServiceTest extends TestCase
{
    private SeoService $service;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new SeoService($this->entityManager);
    }

    public function testGenerateSlugFromText(): void
    {
        $slug = $this->service->generateSlug('Papier Peint Tropical Paradise');
        $this->assertEquals('papier-peint-tropical-paradise', $slug);
    }

    public function testGenerateSlugWithSpecialCharacters(): void
    {
        $slug = $this->service->generateSlug('Café & Thé - Édition Spéciale');
        $this->assertEquals('cafe-the-edition-speciale', $slug);
    }

    public function testGenerateSlugWithNumbers(): void
    {
        $slug = $this->service->generateSlug('Produit 2024 - Version 3.0');
        $this->assertEquals('produit-2024-version-30', $slug);
    }

    public function testGenerateSlugWithMultipleSpaces(): void
    {
        $slug = $this->service->generateSlug('Test    Multiple     Spaces');
        $this->assertEquals('test-multiple-spaces', $slug);
    }

    public function testGenerateSlugEmpty(): void
    {
        $slug = $this->service->generateSlug('');
        $this->assertEquals('', $slug);
    }

    public function testCreateOrUpdateProductSeoNew(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getSeo')->willReturn(null);
        $product->method('getVariants')->willReturn(new ArrayCollection());
        $product->method('getImages')->willReturn(new ArrayCollection());
        $product->method('getName')->willReturn('Test Product');
        $product->method('getDescription')->willReturn('Test description');

        $data = [
            'seoTitle' => 'Test SEO Title',
            'metaDescription' => 'Test meta description',
            'slug' => 'test-slug',
            'canonicalUrl' => 'https://example.com/test',
            'noindex' => false,
            'nofollow' => false,
            'schemaReady' => true,
        ];

        $product->expects($this->once())
            ->method('setSeo')
            ->with($this->isInstanceOf(ProductSeo::class));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ProductSeo::class));



        $seo = $this->service->createOrUpdateProductSeo($product, $data);

        $this->assertInstanceOf(ProductSeo::class, $seo);
        $this->assertEquals('Test SEO Title', $seo->getSeoTitle());
        $this->assertEquals('Test meta description', $seo->getMetaDescription());
        $this->assertEquals('test-slug', $seo->getSlug());
        $this->assertEquals('https://example.com/test', $seo->getCanonicalUrl());
        $this->assertFalse($seo->isNoindex());
        $this->assertFalse($seo->isNofollow());
        $this->assertTrue($seo->isSchemaReady());
    }

    public function testCreateOrUpdateProductSeoUpdate(): void
    {
        $existingSeo = new ProductSeo();
        $existingSeo->setSeoTitle('Old Title');
        $existingSeo->setMetaDescription('Old description');
        $existingSeo->setSlug('old-slug');

        $product = $this->createMock(Product::class);
        $product->method('getSeo')->willReturn($existingSeo);

        $data = [
            'seoTitle' => 'Updated Title',
            'metaDescription' => 'Updated description',
            'slug' => 'updated-slug',
        ];



        $seo = $this->service->createOrUpdateProductSeo($product, $data);

        $this->assertEquals('Updated Title', $seo->getSeoTitle());
        $this->assertEquals('Updated description', $seo->getMetaDescription());
        $this->assertEquals('updated-slug', $seo->getSlug());
    }

    public function testCreateOrUpdateArticleSeo(): void
    {
        $article = $this->createMock(Article::class);
        $article->method('getSeo')->willReturn(null);

        $data = [
            'seoTitle' => 'Article SEO Title',
            'metaDescription' => 'Article meta description',
            'slug' => 'article-slug',
        ];

        $article->expects($this->once())
            ->method('setSeo')
            ->with($this->isInstanceOf(ArticleSeo::class));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ArticleSeo::class));



        $seo = $this->service->createOrUpdateArticleSeo($article, $data);

        $this->assertInstanceOf(ArticleSeo::class, $seo);
        $this->assertEquals('Article SEO Title', $seo->getSeoTitle());
    }

    public function testGenerateStructuredDataForProduct(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getDescription')->willReturn('Test description');
        $product->method('getPrice')->willReturn('99.99');
        $product->method('getSlug')->willReturn('test-product');
        $product->method('getVariants')->willReturn(new ArrayCollection());
        $product->method('getImages')->willReturn(new ArrayCollection());

        $structuredData = $this->service->generateStructuredData($product);

        $this->assertIsArray($structuredData);
        $this->assertEquals('Product', $structuredData['@type']);
        $this->assertEquals('Test Product', $structuredData['name']);
        $this->assertEquals('Test description', $structuredData['description']);
        $this->assertArrayHasKey('offers', $structuredData);
        $this->assertEquals('99.99', $structuredData['offers']['price']);
    }

    public function testValidateSlugLength(): void
    {
        $validSlug = 'valid-slug';
        $this->assertTrue($this->service->validateSlug($validSlug));

        $tooLongSlug = str_repeat('a', 256);
        $this->assertFalse($this->service->validateSlug($tooLongSlug));
    }

    public function testValidateSlugFormat(): void
    {
        $this->assertTrue($this->service->validateSlug('valid-slug-123'));
        $this->assertTrue($this->service->validateSlug('slug'));
        $this->assertFalse($this->service->validateSlug('Invalid Slug'));
        $this->assertFalse($this->service->validateSlug('slug_with_underscore'));
        $this->assertFalse($this->service->validateSlug('slug@special'));
    }
}
