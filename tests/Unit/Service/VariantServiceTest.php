<?php

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Service\VariantService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class VariantServiceTest extends TestCase
{
    private VariantService $service;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new VariantService($this->entityManager);
    }

    public function testCreateVariant(): void
    {
        $product = $this->createMock(Product::class);
        $data = [
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Variant',
            'dimensions' => '100x200',
            'pricePerM2' => 5.99,
            'stock' => 10,
            'isActive' => true,
        ];

        $product->expects($this->once())
            ->method('addVariant')
            ->with($this->isInstanceOf(ProductVariant::class));

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ProductVariant::class));

        $variant = $this->service->createVariant($product, $data);

        $this->assertInstanceOf(ProductVariant::class, $variant);
        $this->assertEquals('TEST-SKU-001', $variant->getSku());
        $this->assertEquals('Test Variant', $variant->getName());
        $this->assertEquals('100x200', $variant->getDimensions());
        $this->assertEquals(5.99, $variant->getPricePerM2());
        $this->assertEquals(10, $variant->getStock());
        $this->assertTrue($variant->isActive());
    }

    public function testCreateVariantWithDefaultValues(): void
    {
        $product = $this->createMock(Product::class);
        $data = [
            'sku' => 'TEST-SKU-002',
            'name' => 'Test Variant 2',
            'pricePerM2' => 7.50,
        ];

        $product->expects($this->once())
            ->method('addVariant');

        $this->entityManager->expects($this->once())
            ->method('persist');

        $variant = $this->service->createVariant($product, $data);

        $this->assertEquals(0, $variant->getStock());
        $this->assertTrue($variant->isActive());
        $this->assertNull($variant->getDimensions());
    }

    public function testUpdateVariant(): void
    {
        $variant = new ProductVariant();
        $variant->setSku('OLD-SKU');
        $variant->setName('Old Name');
        $variant->setStock(5);

        $data = [
            'sku' => 'NEW-SKU',
            'name' => 'New Name',
            'stock' => 15,
            'dimensions' => '150x250',
            'pricePerM2' => 8.99,
            'isActive' => false,
        ];

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updated = $this->service->updateVariant($variant, $data);

        $this->assertEquals('NEW-SKU', $updated->getSku());
        $this->assertEquals('New Name', $updated->getName());
        $this->assertEquals(15, $updated->getStock());
        $this->assertEquals('150x250', $updated->getDimensions());
        $this->assertEquals(8.99, $updated->getPricePerM2());
        $this->assertFalse($updated->isActive());
    }

    public function testUpdateVariantPartial(): void
    {
        $variant = new ProductVariant();
        $variant->setSku('ORIGINAL-SKU');
        $variant->setName('Original Name');
        $variant->setStock(10);
        $variant->setIsActive(true);

        $data = [
            'stock' => 20,
        ];

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updated = $this->service->updateVariant($variant, $data);

        // Seul le stock doit changer
        $this->assertEquals('ORIGINAL-SKU', $updated->getSku());
        $this->assertEquals('Original Name', $updated->getName());
        $this->assertEquals(20, $updated->getStock());
        $this->assertTrue($updated->isActive());
    }

    public function testDeleteVariant(): void
    {
        $variant = new ProductVariant();

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($variant);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->deleteVariant($variant);
    }

    public function testBulkCreateVariants(): void
    {
        $product = $this->createMock(Product::class);
        $variantsData = [
            [
                'sku' => 'BULK-001',
                'name' => 'Variant 1',
                'pricePerM2' => 5.99,
                'stock' => 10,
            ],
            [
                'sku' => 'BULK-002',
                'name' => 'Variant 2',
                'pricePerM2' => 6.99,
                'stock' => 15,
            ],
            [
                'sku' => 'BULK-003',
                'name' => 'Variant 3',
                'pricePerM2' => 7.99,
                'stock' => 20,
            ],
        ];

        $product->expects($this->exactly(3))
            ->method('addVariant');

        $this->entityManager->expects($this->exactly(3))
            ->method('persist');

        $variants = $this->service->bulkCreateVariants($product, $variantsData);

        $this->assertCount(3, $variants);
        $this->assertEquals('BULK-001', $variants[0]->getSku());
        $this->assertEquals('BULK-002', $variants[1]->getSku());
        $this->assertEquals('BULK-003', $variants[2]->getSku());
    }
}
