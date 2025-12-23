<?php

namespace App\Service;

use App\DTO\TypesenseProductPayloadDTO;
use App\Entity\Product;
use App\Enum\ContentStatus;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TypesenseExporter
{
    private string $typesenseHost;
    private string $typesenseApiKey;
    private string $collectionName = 'products';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private HttpClientInterface $httpClient,
        string $typesenseHost = 'http://typesense:8108',
        string $typesenseApiKey = 'default-api-key',
    ) {
        $this->typesenseHost = $typesenseHost;
        $this->typesenseApiKey = $typesenseApiKey;
    }

    public function exportProduct(Product $product): bool
    {
        if ($product->getStatus() !== ContentStatus::PUBLISHED) {
            return $this->deleteProduct($product);
        }

        $payload = $this->buildPayload($product);

        try {
            $response = $this->httpClient->request('PUT', $this->getDocumentUrl($product->getId()), [
                'headers' => [
                    'X-TYPESENSE-API-KEY' => $this->typesenseApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload->toArray(),
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            throw new \RuntimeException("Erreur lors de l'export vers Typesense: " . $e->getMessage());
        }
    }

    public function exportAllPublished(): array
    {
        $products = $this->productRepository->findBy(['status' => ContentStatus::PUBLISHED]);
        $results = [];

        foreach ($products as $product) {
            $results[$product->getId()] = $this->exportProduct($product);
        }

        return $results;
    }

    public function exportAllProducts(bool $dryRun = false): array
    {
        $startTime = microtime(true);
        $products = $this->productRepository->findBy(['status' => ContentStatus::PUBLISHED]);
        
        $exported = 0;
        $skipped = 0;
        $errors = [];
        $sample = null;

        foreach ($products as $product) {
            try {
                $payload = $this->buildPayload($product);
                
                // Capturer un exemple de payload
                if ($sample === null) {
                    $sample = $payload->toArray();
                }

                if (!$dryRun) {
                    $this->exportProduct($product);
                }
                
                $exported++;
            } catch (\Exception $e) {
                $errors[] = "Produit {$product->getName()}: {$e->getMessage()}";
                $skipped++;
            }
        }

        $duration = round(microtime(true) - $startTime, 2);

        return [
            'exported' => $exported,
            'skipped' => $skipped,
            'errors' => $errors,
            'duration' => $duration,
            'sample' => $sample,
        ];
    }

    public function deleteProduct(Product $product): bool
    {
        try {
            $response = $this->httpClient->request('DELETE', $this->getDocumentUrl($product->getId()), [
                'headers' => [
                    'X-TYPESENSE-API-KEY' => $this->typesenseApiKey,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            // Ignorer les erreurs de suppression (le document n'existe peut-être pas)
            return true;
        }
    }

    public function deleteAllByQuery(array $filters): bool
    {
        try {
            $filterQuery = $this->buildFilterQuery($filters);

            $response = $this->httpClient->request('DELETE', "{$this->typesenseHost}/collections/{$this->collectionName}/documents?filter_by={$filterQuery}", [
                'headers' => [
                    'X-TYPESENSE-API-KEY' => $this->typesenseApiKey,
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            throw new \RuntimeException("Erreur lors de la suppression en masse: " . $e->getMessage());
        }
    }

    private function buildPayload(Product $product): TypesenseProductPayloadDTO
    {
        $payload = new TypesenseProductPayloadDTO();
        $payload->id = (string) $product->getId();
        $payload->name = $product->getName();
        $payload->description = $product->getDescription();
        $payload->price = (float) ($product->getPrice() ?? 0);
        $payload->slug = $product->getSlug();
        $payload->status = $product->getStatus()->value;
        $payload->publishedAt = $product->getPublishedAt() ?? new \DateTimeImmutable();

        // Variantes
        foreach ($product->getVariants() as $variant) {
            if ($variant->isActive()) {
                $payload->variants[] = [
                    'id' => (string) $variant->getId(),
                    'sku' => $variant->getSku(),
                    'name' => $variant->getName(),
                    'dimensions' => $variant->getDimensions(),
                    'pricePerM2' => (float) $variant->getPricePerM2(),
                    'stock' => $variant->getStock(),
                ];
            }
        }

        // Catégories
        foreach ($product->getCategories() as $category) {
            $payload->categories[] = [
                'id' => (string) $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
            ];
        }

        // Tags
        foreach ($product->getTags() as $tag) {
            $payload->tags[] = [
                'id' => (string) $tag->getId(),
                'name' => $tag->getName(),
                'slug' => $tag->getSlug(),
            ];
        }

        // Images
        foreach ($product->getImages() as $image) {
            $payload->images[] = [
                'id' => (string) $image->getId(),
                'url' => $image->getUrl(),
                'altText' => $image->getAltText(),
                'dpi' => $image->getDpi(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
            ];
        }

        // SEO
        if ($seo = $product->getSeo()) {
            $payload->seoTitle = $seo->getSeoTitle();
            $payload->metaDescription = $seo->getMetaDescription();
        }

        return $payload;
    }

    private function getDocumentUrl(string $productId): string
    {
        return "{$this->typesenseHost}/collections/{$this->collectionName}/documents/{$productId}";
    }

    private function buildFilterQuery(array $filters): string
    {
        $parts = [];
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $parts[] = "{$key}:[" . implode(',', $value) . "]";
            } else {
                $parts[] = "{$key}:{$value}";
            }
        }

        return urlencode(implode(' && ', $parts));
    }
}
