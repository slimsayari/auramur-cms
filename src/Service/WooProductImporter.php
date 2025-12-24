<?php

namespace App\Service;

use App\DTO\WooProductImportDTO;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\Tag;
use App\Entity\WooImportLog;
use App\Enum\ContentStatus;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class WooProductImporter
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private VariantService $variantService,
        private SeoService $seoService,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ) {}

    public function importFromJson(array $data): WooImportLog
    {
        $log = new WooImportLog();
        $log->setStatus('processing');
        $log->setMetadata(['source' => 'json', 'itemCount' => count($data)]);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        try {
            foreach ($data as $productData) {
                $this->importSingleProduct($productData, $log);
            }

            $log->setStatus('success');
            $log->setCompletedAt(new \DateTimeImmutable());
        } catch (\Exception $e) {
            $log->setStatus('failed');
            $log->addError($e->getMessage());
            $log->setCompletedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        return $log;
    }

    public function importFromCsv(string $filePath): WooImportLog
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Fichier CSV non trouvé : $filePath");
        }

        $data = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }

        return $this->importFromJson($data);
    }
    public function importSingleProduct(array $data, ?WooImportLog $log = null): array
    {
        try {
            // Désérialiser en DTO
            $dto = $this->serializer->deserialize(
                json_encode($data),
                WooProductImportDTO::class,
                'json'
            );

            // Valider
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                $errorMsg = "Validation échouée pour produit {$dto->wooId}: " . (string) $errors;
                if ($log) $log->addError($errorMsg);
                return ['success' => false, 'error' => $errorMsg];
            }

            // TODO: Vérifier si le produit existe déjà (désactivé à cause du bug UUID)
            // $existingProduct = $this->entityManager
            //     ->getRepository(Product::class)
            //     ->findOneBy(['sku' => $data['sku'] ?? $dto->wooId]);
            //
            // if ($existingProduct) {
            //     $errorMsg = "Produit avec SKU {$data['sku']} existe déjà";
            //     if ($log) $log->addError($errorMsg);
            //     return ['success' => false, 'error' => $errorMsg];
            // }

            // Créer le produit
            $product = new Product();
            $slug = $dto->slug ?? $this->seoService->generateSlug($dto->title);
            $product->setSlug($slug);
            $product->setName($dto->title);
            $product->setDescription($dto->description);
            $product->setSku($data['sku'] ?? $dto->wooId);
            $product->setPrice($data['price'] ?? 0);
            $product->setStatus(ContentStatus::DRAFT);

            // Ajouter les catégories
            foreach ($dto->categories as $categoryName) {
                $category = $this->getOrCreateCategory($categoryName);
                $product->addCategory($category);
            }

            // Ajouter les tags
            foreach ($dto->tags as $tagName) {
                $tag = $this->getOrCreateTag($tagName);
                $product->addTag($tag);
            }

            // Ajouter les images
            foreach ($dto->images as $imageData) {
                $image = new ProductImage();
                $image->setProduct($product);
                $image->setUrl($imageData['url'] ?? $imageData);
                $image->setAltText($imageData['alt'] ?? null);
                $image->setFormat('jpg');
                $image->setDpi(300);
                $image->setWidth(1920);
                $image->setHeight(1080);
                $product->addImage($image);
                $this->entityManager->persist($image);
                if ($log) $log->setImagesImported($log->getImagesImported() + 1);
            }

            // Ajouter les variantes
            if (!empty($dto->variants)) {
                $variants = $this->variantService->bulkCreateVariants($product, $dto->variants);
                if ($log) $log->setVariantsImported($log->getVariantsImported() + count($variants));
            }

            $this->entityManager->persist($product);
            
            // Créer le SEO (après persist pour que l'ID soit disponible)
            $seo = $this->seoService->createOrUpdateProductSeo($product, [
                'seoTitle' => $dto->seoTitle ?? $dto->title,
                'metaDescription' => $dto->metaDescription ?? substr($dto->description, 0, 160),
                'slug' => $dto->slug,
                'schemaReady' => true,
            ]);
            $product->setSeo($seo);
            
            $this->entityManager->flush();
            
            if ($log) $log->setProductsImported($log->getProductsImported() + 1);
            
            return ['success' => true, 'product_id' => $product->getId()];
        } catch (\Exception $e) {
            $errorMsg = "Erreur lors de l'import du produit: " . $e->getMessage();
            if ($log) $log->addError($errorMsg);
            return ['success' => false, 'error' => $errorMsg];
        }
    }

    private function getOrCreateCategory(string $categoryName): Category
    {
        $slug = $this->seoService->generateSlug($categoryName);
        $category = $this->categoryRepository->findOneBy(['slug' => $slug]);

        if (!$category) {
            $category = new Category();
            $category->setName($categoryName);
            $category->setSlug($slug);
            $this->entityManager->persist($category);
        }

        return $category;
    }

    private function getOrCreateTag(string $tagName): Tag
    {
        $slug = $this->seoService->generateSlug($tagName);
        $tag = $this->tagRepository->findOneBy(['slug' => $slug]);

        if (!$tag) {
            $tag = new Tag();
            $tag->setName($tagName);
            $tag->setSlug($slug);
            $this->entityManager->persist($tag);
        }

        return $tag;
    }
}
