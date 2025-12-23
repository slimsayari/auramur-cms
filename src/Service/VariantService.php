<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductVariant;
use Doctrine\ORM\EntityManagerInterface;

class VariantService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function createVariant(Product $product, array $data): ProductVariant
    {
        $variant = new ProductVariant();
        $variant->setProduct($product);
        $variant->setSku($data['sku']);
        $variant->setName($data['name']);
        $variant->setDimensions($data['dimensions'] ?? null);
        $variant->setPricePerM2($data['pricePerM2']);
        $variant->setStock($data['stock'] ?? 0);
        $variant->setIsActive($data['isActive'] ?? true);

        $product->addVariant($variant);
        $this->entityManager->persist($variant);

        return $variant;
    }

    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        if (isset($data['sku'])) {
            $variant->setSku($data['sku']);
        }
        if (isset($data['name'])) {
            $variant->setName($data['name']);
        }
        if (isset($data['dimensions'])) {
            $variant->setDimensions($data['dimensions']);
        }
        if (isset($data['pricePerM2'])) {
            $variant->setPricePerM2($data['pricePerM2']);
        }
        if (isset($data['stock'])) {
            $variant->setStock($data['stock']);
        }
        if (isset($data['isActive'])) {
            $variant->setIsActive($data['isActive']);
        }

        $variant->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $variant;
    }

    public function deleteVariant(ProductVariant $variant): void
    {
        $this->entityManager->remove($variant);
        $this->entityManager->flush();
    }

    public function bulkCreateVariants(Product $product, array $variantsData): array
    {
        $variants = [];
        foreach ($variantsData as $variantData) {
            $variant = new ProductVariant();
            $variant->setProduct($product);
            $variant->setSku($variantData['sku']);
            $variant->setName($variantData['name']);
            $variant->setDimensions($variantData['dimensions'] ?? null);
            $variant->setPricePerM2($variantData['pricePerM2']);
            $variant->setStock($variantData['stock'] ?? 0);
            $variant->setIsActive($variantData['isActive'] ?? true);
            
            $product->addVariant($variant);
            $this->entityManager->persist($variant);
            $variants[] = $variant;
        }

        return $variants;
    }
}
