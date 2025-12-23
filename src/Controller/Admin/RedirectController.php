<?php

namespace App\Controller\Admin;

use App\Entity\Redirect;
use App\Repository\RedirectRepository;
use App\Service\SlugService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/redirects')]
class RedirectController extends AbstractController
{
    public function __construct(
        private SlugService $slugService,
        private RedirectRepository $redirectRepository,
    ) {}

    #[Route('', name: 'admin_redirects_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $type = $request->query->get('type');
        $isActive = $request->query->get('active');

        if ($type) {
            $redirects = $this->redirectRepository->findByType($type);
        } else {
            $redirects = $this->redirectRepository->findAllActive();
        }

        if ($isActive !== null) {
            $redirects = array_filter($redirects, fn ($r) => $r->isActive() === ($isActive === 'true'));
        }

        return $this->json([
            'count' => count($redirects),
            'items' => array_map(fn ($r) => [
                'id' => $r->getId(),
                'sourcePath' => $r->getSourcePath(),
                'targetPath' => $r->getTargetPath(),
                'type' => $r->getType(),
                'isActive' => $r->isActive(),
                'reason' => $r->getReason(),
                'createdAt' => $r->getCreatedAt(),
            ], $redirects),
        ]);
    }

    #[Route('', name: 'admin_redirect_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['sourcePath']) || !isset($data['targetPath'])) {
                return $this->json(['error' => 'sourcePath et targetPath sont requis'], 422);
            }

            $redirect = $this->slugService->createRedirect(
                $data['sourcePath'],
                $data['targetPath'],
                $data['type'] ?? '301',
                $data['reason'] ?? null
            );

            return $this->json([
                'id' => $redirect->getId(),
                'sourcePath' => $redirect->getSourcePath(),
                'targetPath' => $redirect->getTargetPath(),
                'type' => $redirect->getType(),
                'message' => 'Redirection créée',
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'admin_redirect_update', methods: ['PATCH'])]
    public function update(Redirect $redirect, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['targetPath'])) {
                $redirect->setTargetPath($data['targetPath']);
            }
            if (isset($data['type'])) {
                $redirect->setType($data['type']);
            }
            if (isset($data['isActive'])) {
                $redirect->setIsActive($data['isActive']);
            }
            if (isset($data['reason'])) {
                $redirect->setReason($data['reason']);
            }

            $redirect->setUpdatedAt(new \DateTimeImmutable());
            $this->entityManager->flush();

            return $this->json([
                'id' => $redirect->getId(),
                'message' => 'Redirection mise à jour',
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'admin_redirect_delete', methods: ['DELETE'])]
    public function delete(Redirect $redirect): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $this->entityManager->remove($redirect);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }
}
