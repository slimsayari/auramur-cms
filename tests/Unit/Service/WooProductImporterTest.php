<?php

namespace App\Tests\Unit\Service;

use App\Entity\Product;
use App\Entity\WooImportLog;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use App\Service\SeoService;
use App\Service\VariantService;
use App\Service\WooProductImporter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class WooProductImporterTest extends TestCase
{
    private WooProductImporter $importer;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private CategoryRepository $categoryRepository;
    private TagRepository $tagRepository;
    private SeoService $seoService;
    private VariantService $variantService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->tagRepository = $this->createMock(TagRepository::class);
        $this->seoService = $this->createMock(SeoService::class);
        $this->variantService = $this->createMock(VariantService::class);

        $this->importer = new WooProductImporter(
            $this->entityManager,
            $this->serializer,
            $this->validator,
            $this->variantService,
            $this->seoService,
            $this->categoryRepository,
            $this->tagRepository
        );
    }

    public function testImportJsonFile(): void
    {
        $jsonContent = json_encode([
            'products' => [
                [
                    'wooId' => '123',
                    'title' => 'Test Product',
                    'description' => 'Test description',
                    'slug' => 'test-product',
                    'price' => 99.99,
                    'sku' => 'TEST-SKU',
                    'categories' => ['Category 1'],
                    'tags' => ['Tag 1'],
                    'images' => [],
                    'variants' => [],
                ]
            ]
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'woo_test_');
        file_put_contents($tempFile, $jsonContent);

        $this->validator->method('validate')->willReturn(new ConstraintViolationList());
        $this->seoService->method('generateSlug')->willReturnCallback(fn($title) => strtolower(str_replace(' ', '-', $title)));

        $this->entityManager->expects($this->atLeastOnce())
            ->method('persist');

        $this->entityManager->expects($this->atLeastOnce())
            ->method('flush');

        $data = json_decode(file_get_contents($tempFile), true);
        $log = $this->importer->importFromJson($data['products']);

        $this->assertInstanceOf(WooImportLog::class, $log);
        $this->assertEquals('completed', $log->getStatus());
        $this->assertGreaterThan(0, $log->getProductsImported());

        unlink($tempFile);
    }
}
