<?php

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\ProductImage;
use App\Entity\ProductSeo;
use App\Enum\ContentStatus;
use App\Exception\ValidationException;
use App\Service\PublicationWorkflowService;
use App\Service\WebhookDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PublicationWorkflowServiceTest extends TestCase
{
    private PublicationWorkflowService $service;
    private EntityManagerInterface $entityManager;
    private WebhookDispatcher $webhookDispatcher;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->webhookDispatcher = $this->createMock(WebhookDispatcher::class);
        
        $this->service = new PublicationWorkflowService(
            $this->entityManager,
            $this->webhookDispatcher
        );
    }

    public function testSubmitForReviewFromDraft(): void
    {
        $product = $this->createProduct(ContentStatus::DRAFT);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->submitForReview($product);

        $this->assertEquals(ContentStatus::READY_FOR_REVIEW, $product->getStatus());
    }

    public function testSubmitForReviewFromInvalidStatus(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Impossible de soumettre pour révision depuis le statut');

        $product = $this->createProduct(ContentStatus::PUBLISHED);
        $this->service->submitForReview($product);
    }

    public function testApproveFromReadyForReview(): void
    {
        $product = $this->createProduct(ContentStatus::READY_FOR_REVIEW);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->approve($product);

        $this->assertEquals(ContentStatus::VALIDATED, $product->getStatus());
    }

    public function testApproveFromInvalidStatus(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Impossible d\'approuver depuis le statut');

        $product = $this->createProduct(ContentStatus::DRAFT);
        $this->service->approve($product);
    }

    public function testPublishWithValidProduct(): void
    {
        $product = $this->createValidProduct();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->webhookDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('product.published', $this->anything());

        $this->service->publish($product);

        $this->assertEquals(ContentStatus::PUBLISHED, $product->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getPublishedAt());
    }

    public function testPublishWithoutVariants(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Au moins une variante active est requise');

        $product = $this->createProduct(ContentStatus::VALIDATED);
        // Pas de variantes
        $this->service->publish($product);
    }

    public function testPublishWithoutImages(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Au moins une image est requise');

        $product = $this->createProduct(ContentStatus::VALIDATED);
        $product->addVariant($this->createVariant());
        // Pas d'images
        $this->service->publish($product);
    }

    public function testPublishWithoutSeo(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('La configuration SEO est requise');

        $product = $this->createProduct(ContentStatus::VALIDATED);
        $product->addVariant($this->createVariant());
        $product->addImage($this->createImage());
        // Pas de SEO
        $this->service->publish($product);
    }

    public function testUnpublish(): void
    {
        $product = $this->createProduct(ContentStatus::PUBLISHED);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->webhookDispatcher->expects($this->once())
            ->method('dispatch')
            ->with('product.unpublished', $this->anything());

        $this->service->unpublish($product);

        $this->assertEquals(ContentStatus::DRAFT, $product->getStatus());
        $this->assertNull($product->getPublishedAt());
    }

    public function testArchive(): void
    {
        $product = $this->createProduct(ContentStatus::PUBLISHED);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->archive($product);

        $this->assertEquals(ContentStatus::ARCHIVED, $product->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getArchivedAt());
    }

    private function createProduct(ContentStatus $status): Product
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStatus', 'setStatus', 'getVariants', 'getImages', 'getSeo', 'setPublishedAt', 'getPublishedAt', 'setArchivedAt'])
            ->getMock();

        $product->method('getStatus')->willReturn($status);
        $product->method('getVariants')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());
        $product->method('getImages')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());
        $product->method('getSeo')->willReturn(null);

        return $product;
    }

    private function createValidProduct(): Product
    {
        $product = $this->createProduct(ContentStatus::VALIDATED);
        
        $variant = $this->createVariant();
        $image = $this->createImage();
        $seo = $this->createMock(ProductSeo::class);

        $variants = new \Doctrine\Common\Collections\ArrayCollection([$variant]);
        $images = new \Doctrine\Common\Collections\ArrayCollection([$image]);

        $product->method('getVariants')->willReturn($variants);
        $product->method('getImages')->willReturn($images);
        $product->method('getSeo')->willReturn($seo);

        return $product;
    }

    private function createVariant(): ProductVariant
    {
        $variant = $this->createMock(ProductVariant::class);
        $variant->method('isActive')->willReturn(true);
        $variant->method('getStock')->willReturn(10);
        return $variant;
    }

    private function createImage(): ProductImage
    {
        return $this->createMock(ProductImage::class);
    }
}
