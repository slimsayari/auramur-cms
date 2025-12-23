<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Service\VariantService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/products/{productId}/variants')]
class VariantController extends AbstractController
{
    public function __construct(
        private VariantService $variantService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'admin_variant_list', methods: ['GET'])]
    public function list(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $variants = $product->getVariants()->toArray();

        return $this->json([
            'count' => count($variants),
            'items' => array_map(fn ($v) => [
                'id' => $v->getId(),
                'sku' => $v->getSku(),
                'name' => $v->getName(),
                'dimensions' => $v->getDimensions(),
                'pricePerM2' => $v->getPricePerM2(),
                'stock' => $v->getStock(),
                'isActive' => $v->isActive(),
            ], $variants),
        ]);
    }

    #[Route('', name: 'admin_variant_create', methods: ['POST'])]
    public function create(Product $product, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            // Valider les données requises
            if (!isset($data['sku']) || !isset($data['name']) || !isset($data['pricePerM2'])) {
                return $this->json(['error' => 'SKU, nom et prix sont requis'], 422);
            }

            $variant = $this->variantService->createVariant($product, $data);

            return $this->json([
                'id' => $variant->getId(),
                'sku' => $variant->getSku(),
                'name' => $variant->getName(),
                'message' => 'Variante créée avec succès',
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{variantId}', name: 'admin_variant_update', methods: ['PATCH'])]
    public function update(Product $product, ProductVariant $variant, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifier que la variante appartient au produit
        if ($variant->getProduct()->getId() !== $product->getId()) {
            return $this->json(['error' => 'Variante non trouvée'], 404);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $variant = $this->variantService->updateVariant($variant, $data);

            return $this->json([
                'id' => $variant->getId(),
                'sku' => $variant->getSku(),
                'name' => $variant->getName(),
                'message' => 'Variante mise à jour',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{variantId}', name: 'admin_variant_delete', methods: ['DELETE'])]
    public function delete(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérifier que la variante appartient au produit
        if ($variant->getProduct()->getId() !== $product->getId()) {
            return $this->json(['error' => 'Variante non trouvée'], 404);
        }

        try {
            $this->variantService->deleteVariant($variant);

            return $this->json(null, 204);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
