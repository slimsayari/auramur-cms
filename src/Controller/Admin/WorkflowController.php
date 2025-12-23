<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Product;
use App\Service\PublicationWorkflowService;
use App\Service\VersioningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class WorkflowController extends AbstractController
{
    public function __construct(
        private PublicationWorkflowService $workflowService,
        private VersioningService $versioningService,
    ) {}

    #[Route('/products/{id}/workflow/submit-review', name: 'admin_product_submit_review', methods: ['POST'])]
    public function submitProductForReview(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->submitForReview($product);
            $this->versioningService->createProductVersion($product, null, 'Soumis pour révision');

            return $this->json([
                'id' => $product->getId(),
                'status' => $product->getStatus()->value,
                'message' => 'Produit soumis pour révision',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/products/{id}/workflow/approve', name: 'admin_product_approve', methods: ['POST'])]
    public function approveProduct(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->approve($product);
            $this->versioningService->createProductVersion($product, null, 'Approuvé');

            return $this->json([
                'id' => $product->getId(),
                'status' => $product->getStatus()->value,
                'message' => 'Produit approuvé',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/products/{id}/workflow/publish', name: 'admin_product_publish', methods: ['POST'])]
    public function publishProduct(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->publish($product);
            $this->versioningService->createProductVersion($product, null, 'Publié');

            return $this->json([
                'id' => $product->getId(),
                'status' => $product->getStatus()->value,
                'publishedAt' => $product->getPublishedAt(),
                'message' => 'Produit publié',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/products/{id}/workflow/unpublish', name: 'admin_product_unpublish', methods: ['POST'])]
    public function unpublishProduct(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->unpublish($product);

            return $this->json([
                'id' => $product->getId(),
                'status' => $product->getStatus()->value,
                'message' => 'Produit dépublié',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/products/{id}/workflow/archive', name: 'admin_product_archive', methods: ['POST'])]
    public function archiveProduct(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->archive($product);

            return $this->json([
                'id' => $product->getId(),
                'status' => $product->getStatus()->value,
                'archivedAt' => $product->getArchivedAt(),
                'message' => 'Produit archivé',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/products/{id}/workflow/reject-review', name: 'admin_product_reject_review', methods: ['POST'])]
    public function rejectProductReview(Product $product, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);
            $reason = $data['reason'] ?? 'Rejeté sans motif';

            $this->workflowService->rejectReview($product, $reason);

            return $this->json([
                'id' => $product->getId(),
                'status' => $product->getStatus()->value,
                'message' => 'Révision rejetée',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/products/{id}/workflow/transitions', name: 'admin_product_transitions', methods: ['GET'])]
    public function getProductTransitions(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $transitions = $this->workflowService->getAvailableTransitions($product);

        return $this->json([
            'currentStatus' => $product->getStatus()->value,
            'availableTransitions' => array_map(fn ($t) => $t->value, $transitions),
        ]);
    }

    // Articles

    #[Route('/articles/{id}/workflow/submit-review', name: 'admin_article_submit_review', methods: ['POST'])]
    public function submitArticleForReview(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->submitForReview($article);
            $this->versioningService->createArticleVersion($article, null, 'Soumis pour révision');

            return $this->json([
                'id' => $article->getId(),
                'status' => $article->getStatus()->value,
                'message' => 'Article soumis pour révision',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/articles/{id}/workflow/publish', name: 'admin_article_publish', methods: ['POST'])]
    public function publishArticle(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->publish($article);
            $this->versioningService->createArticleVersion($article, null, 'Publié');

            return $this->json([
                'id' => $article->getId(),
                'status' => $article->getStatus()->value,
                'publishedAt' => $article->getPublishedAt(),
                'message' => 'Article publié',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/articles/{id}/workflow/archive', name: 'admin_article_archive', methods: ['POST'])]
    public function archiveArticle(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->workflowService->archive($article);

            return $this->json([
                'id' => $article->getId(),
                'status' => $article->getStatus()->value,
                'archivedAt' => $article->getArchivedAt(),
                'message' => 'Article archivé',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
