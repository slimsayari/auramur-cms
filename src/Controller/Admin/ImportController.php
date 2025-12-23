<?php

namespace App\Controller\Admin;

use App\Repository\WooImportLogRepository;
use App\Service\WooProductImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/import')]
class ImportController extends AbstractController
{
    public function __construct(
        private WooProductImporter $wooProductImporter,
        private WooImportLogRepository $importLogRepository,
    ) {}

    #[Route('/woocommerce', name: 'admin_import_woo', methods: ['POST'])]
    public function importWoocommerce(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['source']) || !in_array($data['source'], ['json', 'csv'])) {
                return $this->json(['error' => 'Source invalide (json ou csv)'], 400);
            }

            if ($data['source'] === 'json') {
                if (!isset($data['data']) || !is_array($data['data'])) {
                    return $this->json(['error' => 'Données JSON invalides'], 400);
                }
                $log = $this->wooProductImporter->importFromJson($data['data']);
            } else {
                if (!isset($data['file_url'])) {
                    return $this->json(['error' => 'URL du fichier CSV requise'], 400);
                }
                // Télécharger le fichier
                $tempFile = tempnam(sys_get_temp_dir(), 'woo_import_');
                file_put_contents($tempFile, file_get_contents($data['file_url']));
                $log = $this->wooProductImporter->importFromCsv($tempFile);
                unlink($tempFile);
            }

            return $this->json([
                'id' => $log->getId(),
                'status' => $log->getStatus(),
                'productsImported' => $log->getProductsImported(),
                'variantsImported' => $log->getVariantsImported(),
                'imagesImported' => $log->getImagesImported(),
                'errors' => $log->getErrors(),
                'completedAt' => $log->getCompletedAt(),
            ], 202);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/woocommerce/logs', name: 'admin_import_logs', methods: ['GET'])]
    public function getImportLogs(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $limit = $request->query->getInt('limit', 10);
        $logs = $this->importLogRepository->findLatestImports($limit);

        return $this->json([
            'count' => count($logs),
            'items' => array_map(fn ($log) => [
                'id' => $log->getId(),
                'status' => $log->getStatus(),
                'productsImported' => $log->getProductsImported(),
                'variantsImported' => $log->getVariantsImported(),
                'imagesImported' => $log->getImagesImported(),
                'importedAt' => $log->getImportedAt(),
                'completedAt' => $log->getCompletedAt(),
                'duration' => $log->getDurationSeconds(),
                'errors' => count($log->getErrors() ?? []),
            ], $logs),
        ]);
    }

    #[Route('/woocommerce/logs/{id}', name: 'admin_import_log_detail', methods: ['GET'])]
    public function getImportLogDetail(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $log = $this->importLogRepository->find($id);
        if (!$log) {
            return $this->json(['error' => 'Log non trouvé'], 404);
        }

        return $this->json([
            'id' => $log->getId(),
            'status' => $log->getStatus(),
            'productsImported' => $log->getProductsImported(),
            'variantsImported' => $log->getVariantsImported(),
            'imagesImported' => $log->getImagesImported(),
            'importedAt' => $log->getImportedAt(),
            'completedAt' => $log->getCompletedAt(),
            'duration' => $log->getDurationSeconds(),
            'errors' => $log->getErrors(),
            'metadata' => $log->getMetadata(),
        ]);
    }
}
