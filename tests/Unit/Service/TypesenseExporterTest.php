<?php

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\ProductImage;
use App\Entity\ProductSeo;
use App\Entity\Category;
use App\Entity\Tag;
use App\Enum\ContentStatus;
use App\Repository\ProductRepository;
use App\Service\TypesenseExporter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TypesenseExporterTest extends TestCase
{
    private TypesenseExporter $exporter;
    private EntityManagerInterface $entityManager;
    private ProductRepository $productRepository;
    private HttpClientInterface $httpClient;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        $this->exporter = new TypesenseExporter(
            $this->entityManager,
            $this->productRepository,
            $this->httpClient,
            'http://localhost:8108',
            'test-api-key'
        );
    }

    public function testExportPublishedProduct(): void
    {
        $product = $this->createPublishedProduct();

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                $this->stringContains('/collections/products/documents/'),
                $this->callback(function ($options) {
                    return isset($options['headers']['X-TYPESENSE-API-KEY'])
                        && $options['headers']['X-TYPESENSE-API-KEY'] === 'test-api-key'
                        && isset($options['json']);
                })
            )
            ->willReturn($response);

        $result = $this->exporter->exportProduct($product);

        $this->assertTrue($result);
    }

    public function testExportNonPublishedProductCallsDelete(): void
    {
        $product = $this->createProduct(ContentStatus::DRAFT);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', $this->anything())
            ->willReturn($response);

        $result = $this->exporter->exportProduct($product);

        $this->assertTrue($result);
    }

    public function testExportAllProductsDryRun(): void
    {
        $products = [
            $this->createPublishedProduct(),
            $this->createPublishedProduct(),
        ];

        $this->productRepository->method('findBy')
            ->with(['status' => ContentStatus::PUBLISHED])
            ->willReturn($products);

        $this->httpClient->expects($this->never())
            ->method('request');

        $result = $this->exporter->exportAllProducts(true);

        $this->assertEquals(2, $result['exported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
        $this->assertNotNull($result['sample']);
        $this->assertIsArray($result['sample']);
    }

    public function testExportAllProductsReal(): void
    {
        $products = [
            $this->createPublishedProduct(),
        ];

        $this->productRepository->method('findBy')
            ->with(['status' => ContentStatus::PUBLISHED])
            ->willReturn($products);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $result = $this->exporter->exportAllProducts(false);

        $this->assertEquals(1, $result['exported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
    }

    public function testExportAllProductsWithErrors(): void
    {
        $product1 = $this->createPublishedProduct();
        $product2 = $this->createPublishedProduct();

        $this->productRepository->method('findBy')
            ->willReturn([$product1, $product2]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $response,
                $this->throwException(new \Exception('Network error'))
            );

        $result = $this->exporter->exportAllProducts(false);

        $this->assertEquals(1, $result['exported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(1, $result['errors']);
    }

    public function testDeleteProduct(): void
    {
        $product = $this->createProduct(ContentStatus::DRAFT);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('DELETE', $this->anything())
            ->willReturn($response);

        $result = $this->exporter->deleteProduct($product);

        $this->assertTrue($result);
    }

    public function testDeleteProductIgnoresErrors(): void
    {
        $product = $this->createProduct(ContentStatus::DRAFT);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Not found'));

        $result = $this->exporter->deleteProduct($product);

        // Doit retourner true même en cas d'erreur
        $this->assertTrue($result);
    }

    private function createProduct(ContentStatus $status): Product
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getStatus', 'getName', 'getDescription', 'getPrice', 'getSlug', 'getPublishedAt', 'getVariants', 'getCategories', 'getTags', 'getImages', 'getSeo'])
            ->getMock();

        $product->method('getId')->willReturn(Uuid::v7());
        $product->method('getStatus')->willReturn($status);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getDescription')->willReturn('Test description');
        $product->method('getPrice')->willReturn('99.99');
        $product->method('getSlug')->willReturn('test-product');
        $product->method('getPublishedAt')->willReturn(new \DateTimeImmutable());
        $product->method('getVariants')->willReturn(new ArrayCollection());
        $product->method('getCategories')->willReturn(new ArrayCollection());
        $product->method('getTags')->willReturn(new ArrayCollection());
        $product->method('getImages')->willReturn(new ArrayCollection());
        $product->method('getSeo')->willReturn(null);

        return $product;
    }

    private function createPublishedProduct(): Product
    {
        $product = $this->createProduct(ContentStatus::PUBLISHED);

        // Ajouter une variante
        $variant = $this->createMock(ProductVariant::class);
        $variant->method('getId')->willReturn(Uuid::v7());
        $variant->method('isActive')->willReturn(true);
        $variant->method('getSku')->willReturn('TEST-SKU');
        $variant->method('getName')->willReturn('Test Variant');
        $variant->method('getDimensions')->willReturn('100x200');
        $variant->method('getPricePerM2')->willReturn('5.99');
        $variant->method('getStock')->willReturn(10);

        $product->method('getVariants')->willReturn(new ArrayCollection([$variant]));

        return $product;
    }
}
