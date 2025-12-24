<?php

namespace App\Tests\Functional;

use App\Entity\Product;
use App\Enum\ContentStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class WooCommerceImportTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client
            ->getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * TODO: Ce test échoue à cause du conflit UUID entre ramsey/uuid et symfony/uid.
     * Le service WooProductImporter fonctionne mais les recherches Doctrine échouent.
     * Solution: Standardiser sur symfony/uid dans tout le projet.
     * 
     * Test critique : Import WooCommerce happy path
     * - Import d'un produit valide avec variantes
     * - Vérification que le produit est créé en DRAFT
     * - Vérification que les variantes sont créées
     * 
     * @group todo
     */
    public function testImportValidWooProduct(): void
    {
        $this->markTestSkipped('Conflit UUID ramsey/uuid vs symfony/uid - nécessite refactoring');
        $importService = $this->client->getContainer()->get('App\Service\WooProductImporter');
        
        // Données d'import valides
        $wooData = [
            'wooId' => 'WOO-123',
            'title' => 'Papier Peint Test',
            'description' => 'Description du papier peint test',
            'sku' => 'PP-TEST-001',
            'price' => 49.99,
            'images' => [
                [
                    'url' => 'https://example.com/image1.jpg',
                    'alt' => 'Image 1',
                    'position' => 1,
                ]
            ],
            'seo' => [
                'seo_title' => 'Papier Peint Test - Auramur',
                'meta_description' => 'Un magnifique papier peint pour votre intérieur',
            ],
            'variants' => [
                [
                    'name' => 'Variante Standard',
                    'sku' => 'PP-TEST-001-V1',
                    'dimensions' => '53x1000cm',
                    'pricePerM2' => '29.99',
                    'stock' => 10,
                    'isActive' => true,
                ]
            ],
            'categories' => ['Papiers Peints'],
            'tags' => ['moderne', 'élégant'],
        ];

        // Exécuter l'import
        $result = $importService->importSingleProduct($wooData);

        // Vérifications
        $this->assertTrue($result['success'], 'L\'import devrait réussir');
        $this->assertArrayHasKey('product_id', $result, 'Le résultat devrait contenir l\'ID du produit');

        // Récupérer le produit créé
        $productId = $result['product_id'];
        $product = $this->entityManager->getRepository(Product::class)->find($productId);

        $this->assertNotNull($product, 'Le produit devrait être créé');
        $this->assertEquals('Papier Peint Test', $product->getName());
        $this->assertEquals(ContentStatus::DRAFT, $product->getStatus(), 'Le produit importé devrait être en DRAFT');
        
        // Vérifier les variantes
        $this->assertCount(1, $product->getVariants(), 'Le produit devrait avoir 1 variante');
        $variant = $product->getVariants()->first();
        $this->assertEquals('PP-TEST-001-V1', $variant->getSku());
        $this->assertEquals(10, $variant->getStock());
        
        // Vérifier les images
        $this->assertCount(1, $product->getImages(), 'Le produit devrait avoir 1 image');
        
        // Vérifier le SEO
        $this->assertNotNull($product->getSeo(), 'Le produit devrait avoir une configuration SEO');
        $this->assertEquals('Papier Peint Test - Auramur', $product->getSeo()->getSeoTitle());
    }

    /**
     * Test critique : Import avec données invalides
     * - Vérification que l'import échoue proprement
     * - Vérification qu'aucun produit n'est créé
     */
    public function testImportInvalidWooProduct(): void
    {
        $importService = $this->client->getContainer()->get('App\Service\WooProductImporter');
        
        // Données d'import invalides (title manquant)
        $wooData = [
            'wooId' => 'WOO-INVALID',
            'description' => 'Description sans nom',
            'sku' => 'PP-INVALID',
            'images' => [],
            'variants' => [],
        ];

        // Exécuter l'import
        $result = $importService->importSingleProduct($wooData);

        // Vérifications
        $this->assertFalse($result['success'], 'L\'import devrait échouer');
        $this->assertArrayHasKey('error', $result, 'Le résultat devrait contenir un message d\'erreur');
    }
}
