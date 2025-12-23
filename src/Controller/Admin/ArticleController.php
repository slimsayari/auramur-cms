<?php

namespace App\Controller\Admin;

use App\DTO\ArticleCreateDTO;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Service\ArticleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ValidatorInterface;

#[Route('/api/admin/articles')]
class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleService $articleService,
        private ArticleRepository $articleRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'admin_article_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            $dto = $this->serializer->deserialize(
                json_encode($data),
                ArticleCreateDTO::class,
                'json'
            );

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->json(['error' => (string) $errors], 422);
            }

            $article = $this->articleService->createArticle($dto);

            return $this->json([
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'status' => $article->getStatus()->value,
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'admin_article_update', methods: ['PATCH'])]
    public function update(Article $article, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            $dto = $this->serializer->deserialize(
                json_encode($data),
                ArticleCreateDTO::class,
                'json'
            );

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->json(['error' => (string) $errors], 422);
            }

            $article = $this->articleService->updateArticle($article, $dto);

            return $this->json([
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'status' => $article->getStatus()->value,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/publish', name: 'admin_article_publish', methods: ['PATCH'])]
    public function publish(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $article = $this->articleService->publishArticle($article);

            return $this->json([
                'id' => $article->getId(),
                'status' => $article->getStatus()->value,
                'publishedAt' => $article->getPublishedAt(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'admin_article_delete', methods: ['DELETE'])]
    public function delete(Article $article): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->articleService->deleteArticle($article);

            return $this->json(null, 204);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
