<?php

namespace App\Controller;

use App\DTO\AiGenerationWebhookDTO;
use App\Service\AiGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/webhooks')]
class AiGenerationWebhookController extends AbstractController
{
    public function __construct(
        private AiGenerationService $aiGenerationService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('/ai-generations', name: 'webhook_ai_generation', methods: ['POST'])]
    public function receiveAiGeneration(Request $request): JsonResponse
    {
        // Vérifier le token secret
        $token = $request->headers->get('X-Webhook-Token');
        if ($token !== $_ENV['WEBHOOK_SECRET'] ?? null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $data = json_decode($request->getContent(), true);

            // Désérialiser en DTO
            $dto = $this->serializer->deserialize(
                json_encode($data),
                AiGenerationWebhookDTO::class,
                'json'
            );

            // Valider le DTO
            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->json([
                    'error' => 'Validation failed',
                    'details' => (string) $errors,
                ], 422);
            }

            // Traiter la génération
            $aiGeneration = $this->aiGenerationService->processWebhookGeneration($dto);

            return $this->json([
                'id' => $aiGeneration->getId(),
                'status' => $aiGeneration->getStatus()->value,
                'type' => $aiGeneration->getType()->value,
                'message' => 'AI generation received and queued for validation',
            ], 202);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
