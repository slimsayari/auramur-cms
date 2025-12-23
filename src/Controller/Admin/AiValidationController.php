<?php

namespace App\Controller\Admin;

use App\Entity\AiGeneration;
use App\Repository\AiGenerationRepository;
use App\Service\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/ai-validations')]
class AiValidationController extends AbstractController
{
    public function __construct(
        private ValidationService $validationService,
        private AiGenerationRepository $aiGenerationRepository,
    ) {}

    #[Route('/{id}/validate', name: 'admin_ai_validate', methods: ['PATCH'])]
    public function validate(AiGeneration $aiGeneration): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $aiGeneration = $this->validationService->validateAiGeneration($aiGeneration);

            return $this->json([
                'id' => $aiGeneration->getId(),
                'status' => $aiGeneration->getStatus()->value,
                'validatedAt' => $aiGeneration->getValidatedAt(),
                'message' => 'AI generation validated and applied',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/reject', name: 'admin_ai_reject', methods: ['PATCH'])]
    public function reject(AiGeneration $aiGeneration, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $reason = $data['reason'] ?? 'No reason provided';

            $aiGeneration = $this->validationService->rejectAiGeneration($aiGeneration, $reason);

            return $this->json([
                'id' => $aiGeneration->getId(),
                'status' => $aiGeneration->getStatus()->value,
                'rejectionReason' => $aiGeneration->getRejectionReason(),
                'message' => 'AI generation rejected',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/pending', name: 'admin_ai_pending', methods: ['GET'])]
    public function getPending(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $pending = $this->aiGenerationRepository->findPendingValidation();

        return $this->json([
            'count' => count($pending),
            'items' => array_map(fn ($ag) => [
                'id' => $ag->getId(),
                'type' => $ag->getType()->value,
                'productId' => $ag->getProduct()?->getId(),
                'articleId' => $ag->getArticle()?->getId(),
                'generatedAt' => $ag->getGeneratedAt(),
            ], $pending),
        ]);
    }
}
