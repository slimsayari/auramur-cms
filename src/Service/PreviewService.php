<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\PreviewToken;
use App\Entity\Product;
use App\Repository\PreviewTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class PreviewService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PreviewTokenRepository $tokenRepository,
    ) {}

    public function generateProductPreviewToken(
        Product $product,
        ?\DateInterval $expiresIn = null
    ): PreviewToken {
        $token = new PreviewToken();
        $token->setEntityType('product');
        $token->setEntityId($product->getId());
        $token->setToken(PreviewToken::generateToken());

        if ($expiresIn) {
            $token->setExpiresAt((new \DateTimeImmutable())->add($expiresIn));
        }

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function generateArticlePreviewToken(
        Article $article,
        ?\DateInterval $expiresIn = null
    ): PreviewToken {
        $token = new PreviewToken();
        $token->setEntityType('article');
        $token->setEntityId($article->getId());
        $token->setToken(PreviewToken::generateToken());

        if ($expiresIn) {
            $token->setExpiresAt((new \DateTimeImmutable())->add($expiresIn));
        }

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function validateToken(string $token): ?PreviewToken
    {
        return $this->tokenRepository->findValidByToken($token);
    }

    public function revokeToken(PreviewToken $token): void
    {
        $this->entityManager->remove($token);
        $this->entityManager->flush();
    }

    public function revokeAllTokensForEntity(string $entityType, Uuid $entityId): int
    {
        $tokens = $this->entityManager
            ->getRepository(PreviewToken::class)
            ->findBy(['entityType' => $entityType, 'entityId' => $entityId]);

        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        $this->entityManager->flush();

        return count($tokens);
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->deleteExpired();
    }

    public function getPreviewUrl(PreviewToken $token): string
    {
        return "/api/preview/{$token->getToken()}";
    }
}
