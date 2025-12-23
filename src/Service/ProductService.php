<?php

namespace App\Service;

use App\DTO\ProductCreateDTO;
use App\DTO\ProductUpdateDTO;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\Tag;
use App\Enum\ContentStatus;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ) {}

    public function createProduct(ProductCreateDTO $dto): Product
    {
        $product = new Product();
        $product->setSlug($dto->slug);
        $product->setName($dto->name);
        $product->setDescription($dto->description);
        $product->setSku($dto->sku);
        $product->setPrice($dto->price);

        // Ajouter les catégories
        foreach ($dto->categoryIds as $categoryId) {
            $category = $this->categoryRepository->find($categoryId);
            if ($category) {
                $product->addCategory($category);
            }
        }

        // Ajouter les tags
        foreach ($dto->tagIds as $tagId) {
            $tag = $this->tagRepository->find($tagId);
            if ($tag) {
                $product->addTag($tag);
            }
        }

        // Ajouter les images
        foreach ($dto->images as $index => $imageData) {
            $productImage = new ProductImage();
            $productImage->setUrl($imageData['url']);
            $productImage->setFormat($imageData['format'] ?? null);
            $productImage->setDpi($imageData['dpi'] ?? null);
            $productImage->setWidth($imageData['width'] ?? null);
            $productImage->setHeight($imageData['height'] ?? null);
            $productImage->setAltText($imageData['altText'] ?? null);
            $productImage->setPosition($index);
            $productImage->setIsThumbnail($index === 0);

            $product->addImage($productImage);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }

    public function updateProduct(Product $product, ProductUpdateDTO $dto): Product
    {
        if ($dto->name !== null) {
            $product->setName($dto->name);
        }
        if ($dto->description !== null) {
            $product->setDescription($dto->description);
        }
        if ($dto->sku !== null) {
            $product->setSku($dto->sku);
        }
        if ($dto->price !== null) {
            $product->setPrice($dto->price);
        }

        // Mettre à jour les catégories
        if (!empty($dto->categoryIds)) {
            $product->getCategories()->clear();
            foreach ($dto->categoryIds as $categoryId) {
                $category = $this->categoryRepository->find($categoryId);
                if ($category) {
                    $product->addCategory($category);
                }
            }
        }

        // Mettre à jour les tags
        if (!empty($dto->tagIds)) {
            $product->getTags()->clear();
            foreach ($dto->tagIds as $tagId) {
                $tag = $this->tagRepository->find($tagId);
                if ($tag) {
                    $product->addTag($tag);
                }
            }
        }

        // Mettre à jour les images
        if (!empty($dto->images)) {
            $product->getImages()->clear();
            foreach ($dto->images as $index => $imageData) {
                $productImage = new ProductImage();
                $productImage->setUrl($imageData['url']);
                $productImage->setFormat($imageData['format'] ?? null);
                $productImage->setDpi($imageData['dpi'] ?? null);
                $productImage->setWidth($imageData['width'] ?? null);
                $productImage->setHeight($imageData['height'] ?? null);
                $productImage->setAltText($imageData['altText'] ?? null);
                $productImage->setPosition($index);
                $productImage->setIsThumbnail($index === 0);

                $product->addImage($productImage);
            }
        }

        $product->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $product;
    }

    public function publishProduct(Product $product): Product
    {
        if (empty($product->getImages())) {
            throw new \InvalidArgumentException('Un produit doit avoir au moins une image avant publication');
        }

        $product->setStatus(ContentStatus::PUBLISHED);
        $product->setPublishedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();
    }
}
