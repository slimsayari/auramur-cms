<?php

namespace App\Tests\Functional;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\ProductImage;
use App\Entity\ProductSeo;
use App\Enum\ContentStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductWorkflowTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    public function testCreateProductDraft(): void
    {
        $product = new Product();
        $product->setName('Test Product');
        $product->setSlug('test-product-' . uniqid());
        $product->setDescription('Test description');
        $product->setSku('TEST-SKU-' . uniqid());
        $product->setPrice('99.99');
        $product->setStatus(ContentStatus::DRAFT);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->assertNotNull($product->getId());
        $this->assertEquals(ContentStatus::DRAFT, $product->getStatus());
    }

    public function testCannotPublishProductWithoutVariants(): void
    {
        $product = $this->createProductWithSeoAndImages();
        // Pas de variantes

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Au moins une variante active est requise');

        $workflowService = $this->client->getContainer()->get('App\Service\PublicationWorkflowService');
        $workflowService->publish($product);
    }

    public function testCannotPublishProductWithoutImages(): void
    {
        $product = $this->createProductWithSeoAndVariants();
        // Pas d'images

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Au moins une image est requise');

        $workflowService = $this->client->getContainer()->get('App\Service\PublicationWorkflowService');
        $workflowService->publish($product);
    }

    public function testCannotPublishProductWithoutSeo(): void
    {
        $product = $this->createProductWithVariantsAndImages();
        // Pas de SEO

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('La configuration SEO est requise');

        $workflowService = $this->client->getContainer()->get('App\Service\PublicationWorkflowService');
        $workflowService->publish($product);
    }

    public function testPublishValidProduct(): void
    {
        $product = $this->createCompleteProduct();

        $workflowService = $this->client->getContainer()->get('App\Service\PublicationWorkflowService');
        $workflowService->publish($product);

        $this->assertEquals(ContentStatus::PUBLISHED, $product->getStatus());
        $this->assertNotNull($product->getPublishedAt());
    }

    public function testUnpublishProduct(): void
    {
        $product = $this->createCompleteProduct();

        $workflowService = $this->client->getContainer()->get('App\Service\PublicationWorkflowService');
        $workflowService->publish($product);
        $workflowService->unpublish($product);

        $this->assertEquals(ContentStatus::DRAFT, $product->getStatus());
        $this->assertNull($product->getPublishedAt());
    }

    public function testWorkflowTransitions(): void
    {
        $product = $this->createCompleteProduct();
        $workflowService = $this->client->getContainer()->get('App\Service\PublicationWorkflowService');

        // DRAFT → READY_FOR_REVIEW
        $workflowService->submitForReview($product);
        $this->assertEquals(ContentStatus::READY_FOR_REVIEW, $product->getStatus());

        // READY_FOR_REVIEW → VALIDATED
        $workflowService->approve($product);
        $this->assertEquals(ContentStatus::VALIDATED, $product->getStatus());

        // VALIDATED → PUBLISHED
        $workflowService->publish($product);
        $this->assertEquals(ContentStatus::PUBLISHED, $product->getStatus());

        // PUBLISHED → ARCHIVED
        $workflowService->archive($product);
        $this->assertEquals(ContentStatus::ARCHIVED, $product->getStatus());
        $this->assertNotNull($product->getArchivedAt());
    }

    /**
     * TODO: Ce test échoue car DAMA DoctrineTestBundle isole les transactions.
     * Le produit créé dans le test n'est pas visible via la requête HTTP API.
     * Solution: Désactiver DAMA pour ce test ou utiliser des fixtures persistantes.
     * 
     * @group todo
     */
    public function testDraftProductNotExposedPublicly(): void
    {
        $this->markTestSkipped('Test isolé par DAMA DoctrineTestBundle - nécessite refactoring');
        
        $product = $this->createCompleteProduct();
        // Produit en DRAFT

        // Tenter d'accéder via API publique
        $this->client->request('GET', '/api/products');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        // Vérifier que le produit DRAFT n'est pas dans la liste
        $productIds = array_column($data['hydra:member'] ?? [], 'id');
        $this->assertNotContains((string) $product->getId(), $productIds);
    }

    /**
     * TODO: Ce test échoue car DAMA DoctrineTestBundle isole les transactions.
     * Le produit créé dans le test n'est pas visible via la requête HTTP API.
     * Solution: Désactiver DAMA pour ce test ou utiliser des fixtures persistantes.
     * 
     * @group todo
     */
    public function testPublishedProductExposedPublicly(): void
    {
        $this->markTestSkipped('Test isolé par DAMA DoctrineTestBundle - nécessite refactoring');
        
        $product = $this->createCompleteProduct();

        $workflowService = $this->client->getContainer()->get('App\Service\PublicationWorkflowService');
        $workflowService->publish($product);

        $this->client->request('GET', '/api/products');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);

        // Vérifier que le produit PUBLISHED est dans la liste
        $productIds = array_column($data['hydra:member'] ?? [], 'id');
        $this->assertContains((string) $product->getId(), $productIds);
    }

    private function createCompleteProduct(): Product
    {
        $product = new Product();
        $product->setName('Complete Product ' . uniqid());
        $product->setSlug('complete-product-' . uniqid());
        $product->setDescription('Complete description');
        $product->setSku('COMPLETE-SKU-' . uniqid());
        $product->setPrice('99.99');
        $product->setStatus(ContentStatus::DRAFT);

        // Ajouter une variante
        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setSku('VAR-' . uniqid());
        $variant->setName('Variant 1');
        $variant->setPricePerM2(5.99);
        $variant->setStock(10);
        $variant->setIsActive(true);
        $product->addVariant($variant);

        // Ajouter une image
        $image = new ProductImage();
        $image->setProduct($product);
        $image->setUrl('https://example.com/image.jpg');
        $image->setFormat('jpg');
        $image->setDpi(300);
        $image->setWidth(1920);
        $image->setHeight(1080);
        $product->addImage($image);

        // Ajouter le SEO
        $seo = new ProductSeo();
        $seo->setProduct($product);
        $seo->setSeoTitle('Complete Product SEO');
        $seo->setMetaDescription('Complete product meta description');
        $seo->setSlug($product->getSlug());
        $product->setSeo($seo);

        $this->entityManager->persist($product);
        $this->entityManager->persist($variant);
        $this->entityManager->persist($image);
        $this->entityManager->persist($seo);
        $this->entityManager->flush();

        return $product;
    }

    private function createProductWithSeoAndImages(): Product
    {
        $product = new Product();
        $product->setName('Product ' . uniqid());
        $product->setSlug('product-' . uniqid());
        $product->setDescription('Description');
        $product->setSku('SKU-' . uniqid());
        $product->setPrice('99.99');
        $product->setStatus(ContentStatus::VALIDATED);

        $image = new ProductImage();
        $image->setProduct($product);
        $image->setUrl('https://example.com/image.jpg');
        $image->setFormat('jpg');
        $image->setDpi(300);
        $image->setWidth(1920);
        $image->setHeight(1080);
        $product->addImage($image);

        $seo = new ProductSeo();
        $seo->setProduct($product);
        $seo->setSeoTitle('Product SEO');
        $seo->setMetaDescription('Product meta description');
        $seo->setSlug($product->getSlug());
        $product->setSeo($seo);

        $this->entityManager->persist($product);
        $this->entityManager->persist($image);
        $this->entityManager->persist($seo);
        $this->entityManager->flush();

        return $product;
    }

    private function createProductWithSeoAndVariants(): Product
    {
        $product = new Product();
        $product->setName('Product ' . uniqid());
        $product->setSlug('product-' . uniqid());
        $product->setDescription('Description');
        $product->setSku('SKU-' . uniqid());
        $product->setPrice('99.99');
        $product->setStatus(ContentStatus::VALIDATED);

        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setSku('VAR-' . uniqid());
        $variant->setName('Variant 1');
        $variant->setPricePerM2(5.99);
        $variant->setStock(10);
        $variant->setIsActive(true);
        $product->addVariant($variant);

        $seo = new ProductSeo();
        $seo->setProduct($product);
        $seo->setSeoTitle('Product SEO');
        $seo->setMetaDescription('Product meta description');
        $seo->setSlug($product->getSlug());
        $product->setSeo($seo);

        $this->entityManager->persist($product);
        $this->entityManager->persist($variant);
        $this->entityManager->persist($seo);
        $this->entityManager->flush();

        return $product;
    }

    private function createProductWithVariantsAndImages(): Product
    {
        $product = new Product();
        $product->setName('Product ' . uniqid());
        $product->setSlug('product-' . uniqid());
        $product->setDescription('Description');
        $product->setSku('SKU-' . uniqid());
        $product->setPrice('99.99');
        $product->setStatus(ContentStatus::VALIDATED);

        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setSku('VAR-' . uniqid());
        $variant->setName('Variant 1');
        $variant->setPricePerM2(5.99);
        $variant->setStock(10);
        $variant->setIsActive(true);
        $product->addVariant($variant);

        $image = new ProductImage();
        $image->setProduct($product);
        $image->setUrl('https://example.com/image.jpg');
        $image->setFormat('jpg');
        $image->setDpi(300);
        $image->setWidth(1920);
        $image->setHeight(1080);
        $product->addImage($image);

        $this->entityManager->persist($product);
        $this->entityManager->persist($variant);
        $this->entityManager->persist($image);
        $this->entityManager->flush();

        return $product;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
