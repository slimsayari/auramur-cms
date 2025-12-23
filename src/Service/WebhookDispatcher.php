<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Product;
use App\Entity\WebhookEvent;
use App\Repository\WebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WebhookDispatcher
{
    private const WEBHOOK_ENDPOINTS = [
        'typesense' => 'http://typesense:8108/collections/products/documents',
        'n8n' => 'http://n8n:5678/webhook/cms',
        'cache' => 'http://cache-service:3000/invalidate',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private WebhookEventRepository $eventRepository,
        private HttpClientInterface $httpClient,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function dispatchProductPublished(Product $product): void
    {
        $this->dispatch('product.published', $this->buildProductPayload($product));
    }

    public function dispatchProductUpdated(Product $product): void
    {
        $this->dispatch('product.updated', $this->buildProductPayload($product));
    }

    public function dispatchProductArchived(Product $product): void
    {
        $this->dispatch('product.archived', [
            'id' => (string) $product->getId(),
            'slug' => $product->getSlug(),
            'name' => $product->getName(),
            'archivedAt' => $product->getArchivedAt()?->format('c'),
        ]);
    }

    public function dispatchArticlePublished(Article $article): void
    {
        $this->dispatch('article.published', $this->buildArticlePayload($article));
    }

    public function dispatchArticleUpdated(Article $article): void
    {
        $this->dispatch('article.updated', $this->buildArticlePayload($article));
    }

    public function dispatchArticleArchived(Article $article): void
    {
        $this->dispatch('article.archived', [
            'id' => (string) $article->getId(),
            'slug' => $article->getSlug(),
            'title' => $article->getTitle(),
            'archivedAt' => $article->getArchivedAt()?->format('c'),
        ]);
    }

    private function dispatch(string $event, array $payload): void
    {
        $webhookEvent = new WebhookEvent();
        $webhookEvent->setEvent($event);
        $webhookEvent->setPayload($payload);
        $webhookEvent->setStatus('pending');

        $this->entityManager->persist($webhookEvent);
        $this->entityManager->flush();

        // Dispatcher un événement Symfony pour les listeners
        $this->eventDispatcher->dispatch(
            new \Symfony\Component\EventDispatcher\GenericEvent($event, $payload)
        );
    }

    public function deliverPendingEvents(int $maxRetries = 3): int
    {
        $events = $this->eventRepository->findPending(100);
        $delivered = 0;

        foreach ($events as $event) {
            if ($event->getRetryCount() >= $maxRetries) {
                $event->setStatus('failed');
                $this->entityManager->flush();
                continue;
            }

            try {
                $this->deliverEvent($event);
                $event->setStatus('delivered');
                $event->setDeliveredAt(new \DateTimeImmutable());
                $delivered++;
            } catch (\Exception $e) {
                $event->incrementRetryCount();
                $event->setLastError($e->getMessage());
            }

            $this->entityManager->flush();
        }

        return $delivered;
    }

    private function deliverEvent(WebhookEvent $event): void
    {
        $payload = json_encode($event->getPayload());

        // Envoyer à tous les endpoints configurés
        foreach (self::WEBHOOK_ENDPOINTS as $name => $endpoint) {
            try {
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-Event' => $event->getEvent(),
                        'X-Timestamp' => $event->getCreatedAt()->format('c'),
                    ],
                    'body' => $payload,
                    'timeout' => 5,
                ]);

                if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                    throw new \RuntimeException(
                        "Webhook $name retourned {$response->getStatusCode()}"
                    );
                }
            } catch (\Exception $e) {
                // Logger mais ne pas bloquer les autres endpoints
                error_log("Webhook delivery failed for $name: " . $e->getMessage());
            }
        }
    }

    private function buildProductPayload(Product $product): array
    {
        return [
            'id' => (string) $product->getId(),
            'slug' => $product->getSlug(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'status' => $product->getStatus()->value,
            'publishedAt' => $product->getPublishedAt()?->format('c'),
            'updatedAt' => $product->getUpdatedAt()->format('c'),
        ];
    }

    private function buildArticlePayload(Article $article): array
    {
        return [
            'id' => (string) $article->getId(),
            'slug' => $article->getSlug(),
            'title' => $article->getTitle(),
            'excerpt' => $article->getExcerpt(),
            'status' => $article->getStatus()->value,
            'publishedAt' => $article->getPublishedAt()?->format('c'),
            'updatedAt' => $article->getUpdatedAt()->format('c'),
        ];
    }
}
