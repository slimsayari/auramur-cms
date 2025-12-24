<?php

namespace App\Controller\Api;

use App\Service\WebhookPaymentHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/webhooks')]
class WebhookController extends AbstractController
{
    public function __construct(
        private WebhookPaymentHandler $webhookHandler
    ) {
    }

    #[Route('/stripe', name: 'api_webhook_stripe', methods: ['POST'])]
    public function stripe(Request $request): JsonResponse
    {
        try {
            $payload = $request->getContent();
            $signature = $request->headers->get('stripe-signature');

            if (!$signature) {
                return $this->json(['error' => 'Missing signature'], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->webhookHandler->handleStripeWebhook($payload, $signature);

            if ($result) {
                return $this->json([
                    'message' => 'Webhook processed successfully',
                    'data' => $result
                ]);
            }

            return $this->json(['message' => 'Webhook received but not processed']);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/paypal', name: 'api_webhook_paypal', methods: ['POST'])]
    public function paypal(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $result = $this->webhookHandler->handlePayPalWebhook($data);

            if ($result) {
                return $this->json([
                    'message' => 'Webhook processed successfully',
                    'data' => $result
                ]);
            }

            return $this->json(['message' => 'Webhook received but not processed']);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
