<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Product;
use App\Enum\ContentStatus;
use Doctrine\ORM\EntityManagerInterface;

class PublicationWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function submitForReview(Product|Article $entity): void
    {
        if ($entity->getStatus() !== ContentStatus::DRAFT) {
            throw new \InvalidArgumentException(
                "Seul un brouillon peut être soumis pour révision. Statut actuel: {$entity->getStatus()->value}"
            );
        }

        $entity->setStatus(ContentStatus::READY_FOR_REVIEW);
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function approve(Product|Article $entity): void
    {
        if ($entity->getStatus() !== ContentStatus::READY_FOR_REVIEW) {
            throw new \InvalidArgumentException(
                "Seul un contenu en révision peut être approuvé. Statut actuel: {$entity->getStatus()->value}"
            );
        }

        $entity->setStatus(ContentStatus::VALIDATED);
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function publish(Product|Article $entity): void
    {
        if ($entity->getStatus() === ContentStatus::PUBLISHED) {
            throw new \InvalidArgumentException("Ce contenu est déjà publié.");
        }

        if ($entity->getStatus() === ContentStatus::ARCHIVED) {
            throw new \InvalidArgumentException("Un contenu archivé ne peut pas être republié directement.");
        }

        // Validation spécifique pour les produits : au moins 1 variante active requise
        if ($entity instanceof Product) {
            $this->validateProductForPublication($entity);
        }

        $entity->setStatus(ContentStatus::PUBLISHED);
        $entity->setPublishedAt(new \DateTimeImmutable());
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function unpublish(Product|Article $entity): void
    {
        if ($entity->getStatus() !== ContentStatus::PUBLISHED) {
            throw new \InvalidArgumentException(
                "Seul un contenu publié peut être dépublié. Statut actuel: {$entity->getStatus()->value}"
            );
        }

        $entity->setStatus(ContentStatus::DRAFT);
        $entity->setPublishedAt(null);
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function archive(Product|Article $entity): void
    {
        if ($entity->getStatus() === ContentStatus::ARCHIVED) {
            throw new \InvalidArgumentException("Ce contenu est déjà archivé.");
        }

        $entity->setStatus(ContentStatus::ARCHIVED);
        $entity->setArchivedAt(new \DateTimeImmutable());
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function unarchive(Product|Article $entity): void
    {
        if ($entity->getStatus() !== ContentStatus::ARCHIVED) {
            throw new \InvalidArgumentException(
                "Seul un contenu archivé peut être désarchivé. Statut actuel: {$entity->getStatus()->value}"
            );
        }

        $entity->setStatus(ContentStatus::DRAFT);
        $entity->setArchivedAt(null);
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function rejectReview(Product|Article $entity, string $reason): void
    {
        if ($entity->getStatus() !== ContentStatus::READY_FOR_REVIEW) {
            throw new \InvalidArgumentException(
                "Seul un contenu en révision peut être rejeté. Statut actuel: {$entity->getStatus()->value}"
            );
        }

        $entity->setStatus(ContentStatus::DRAFT);
        $entity->setMetadata(array_merge(
            $entity->getMetadata() ?? [],
            ['rejection_reason' => $reason, 'rejected_at' => (new \DateTimeImmutable())->format('c')]
        ));
        $entity->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function canTransition(Product|Article $entity, ContentStatus $targetStatus): bool
    {
        $currentStatus = $entity->getStatus();

        // Transitions autorisées
        $allowedTransitions = [
            ContentStatus::DRAFT => [ContentStatus::READY_FOR_REVIEW, ContentStatus::ARCHIVED],
            ContentStatus::READY_FOR_REVIEW => [ContentStatus::VALIDATED, ContentStatus::DRAFT, ContentStatus::ARCHIVED],
            ContentStatus::VALIDATED => [ContentStatus::PUBLISHED, ContentStatus::DRAFT, ContentStatus::ARCHIVED],
            ContentStatus::PUBLISHED => [ContentStatus::DRAFT, ContentStatus::ARCHIVED],
            ContentStatus::ARCHIVED => [ContentStatus::DRAFT],
        ];

        return in_array($targetStatus, $allowedTransitions[$currentStatus] ?? []);
    }

    public function getAvailableTransitions(Product|Article $entity): array
    {
        $transitions = [];
        foreach (ContentStatus::cases() as $status) {
            if ($this->canTransition($entity, $status)) {
                $transitions[] = $status;
            }
        }

        return $transitions;
    }

    private function validateProductForPublication(Product $product): void
    {
        // Vérifier qu'il y a au moins une variante active
        $activeVariants = $product->getVariants()->filter(fn($variant) => $variant->isActive());

        if ($activeVariants->isEmpty()) {
            throw new \Exception('Au moins une variante active est requise');
        }

        // Vérifier qu'il y a au moins une image
        if ($product->getImages()->isEmpty()) {
            throw new \Exception('Au moins une image est requise');
        }

        // Vérifier que le SEO est configuré
        if (!$product->getSeo()) {
            throw new \Exception('La configuration SEO est requise');
        }
    }
}
