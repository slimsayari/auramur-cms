<?php

namespace App\Controller;

use App\Repository\RedirectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/redirects')]
class RedirectPublicController extends AbstractController
{
    public function __construct(
        private RedirectRepository $redirectRepository,
    ) {}

    #[Route('/check', name: 'public_redirect_check', methods: ['GET'])]
    public function checkRedirect(Request $request): JsonResponse
    {
        $sourcePath = $request->query->get('path');

        if (!$sourcePath) {
            return $this->json(['error' => 'Paramètre path requis'], 422);
        }

        $redirect = $this->redirectRepository->findActiveBySource($sourcePath);

        if (!$redirect) {
            return $this->json(['found' => false]);
        }

        return $this->json([
            'found' => true,
            'sourcePath' => $redirect->getSourcePath(),
            'targetPath' => $redirect->getTargetPath(),
            'type' => (int) $redirect->getType(),
        ]);
    }
}
