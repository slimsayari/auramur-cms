<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\TypesenseExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/export')]
class ExportController extends AbstractController
{
    public function __construct(
        private TypesenseExporter $typesenseExporter,
        private ProductRepository $productRepository,
    ) {}

    #[Route('/typesense', name: 'admin_export_typesense', methods: ['POST'])]
    public function exportTypesense(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $productIds = $data['productIds'] ?? [];
            $action = $data['action'] ?? 'upsert';

            if (!in_array($action, ['upsert', 'delete'])) {
                return $this->json(['error' => 'Action invalide (upsert ou delete)'], 400);
            }

            if (empty($productIds)) {
                // Exporter tous les produits publiés
                $results = $this->typesenseExporter->exportAllPublished();
                $successCount = array_sum(array_map(fn ($r) => $r ? 1 : 0, $results));

                return $this->json([
                    'status' => 'success',
                    'action' => 'upsert',
                    'productsExported' => count($results),
                    'successCount' => $successCount,
                    'message' => "Export de {$successCount} produits vers Typesense réussi",
                ], 202);
            }

            // Exporter des produits spécifiques
            $results = [];
            foreach ($productIds as $productId) {
                $product = $this->productRepository->find($productId);
                if (!$product) {
                    continue;
                }

                if ($action === 'upsert') {
                    $results[$productId] = $this->typesenseExporter->exportProduct($product);
                } else {
                    $results[$productId] = $this->typesenseExporter->deleteProduct($product);
                }
            }

            $successCount = array_sum(array_map(fn ($r) => $r ? 1 : 0, $results));

            return $this->json([
                'status' => 'success',
                'action' => $action,
                'productsProcessed' => count($results),
                'successCount' => $successCount,
                'message' => "{$action} de {$successCount} produits vers Typesense réussi",
            ], 202);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/typesense/product/{id}', name: 'admin_export_product', methods: ['POST'])]
    public function exportProduct(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $success = $this->typesenseExporter->exportProduct($product);

            if (!$success) {
                return $this->json(['error' => 'Erreur lors de l\'export'], 500);
            }

            return $this->json([
                'id' => $product->getId(),
                'status' => 'exported',
                'message' => 'Produit exporté vers Typesense',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/typesense/product/{id}', name: 'admin_delete_product', methods: ['DELETE'])]
    public function deleteProduct(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $success = $this->typesenseExporter->deleteProduct($product);

            if (!$success) {
                return $this->json(['error' => 'Erreur lors de la suppression'], 500);
            }

            return $this->json([
                'id' => $product->getId(),
                'status' => 'deleted',
                'message' => 'Produit supprimé de Typesense',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
