<?php

namespace App\Service;

use App\Entity\Redirect;
use App\Entity\SlugRegistry;
use App\Repository\RedirectRepository;
use App\Repository\SlugRegistryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class SlugService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SlugRegistryRepository $slugRegistry,
        private RedirectRepository $redirectRepository,
    ) {}

    public function generateSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return $slug;
    }

    public function registerSlug(string $slug, string $entityType, Uuid $entityId): SlugRegistry
    {
        // Vérifier l'unicité
        if ($this->slugRegistry->slugExists($slug, $entityId)) {
            throw new \InvalidArgumentException("Le slug '$slug' est déjà utilisé.");
        }

        // Supprimer l'ancien slug si existe
        $oldSlug = $this->slugRegistry->findByEntity($entityType, $entityId);
        if ($oldSlug) {
            $this->entityManager->remove($oldSlug);
        }

        $slugReg = new SlugRegistry();
        $slugReg->setSlug($slug);
        $slugReg->setEntityType($entityType);
        $slugReg->setEntityId($entityId);

        $this->entityManager->persist($slugReg);
        $this->entityManager->flush();

        return $slugReg;
    }

    public function changeSlug(string $oldSlug, string $newSlug, string $entityType, Uuid $entityId): void
    {
        // Vérifier que le nouveau slug est unique
        if ($this->slugRegistry->slugExists($newSlug, $entityId)) {
            throw new \InvalidArgumentException("Le slug '$newSlug' est déjà utilisé.");
        }

        // Enregistrer le nouveau slug
        $this->registerSlug($newSlug, $entityType, $entityId);

        // Créer une redirection 301 de l'ancien vers le nouveau
        $this->createRedirect(
            "/$entityType/$oldSlug",
            "/$entityType/$newSlug",
            '301',
            "Changement de slug de '$oldSlug' à '$newSlug'"
        );
    }

    public function createRedirect(
        string $sourcePath,
        string $targetPath,
        string $type = '301',
        ?string $reason = null
    ): Redirect {
        // Vérifier que la source n'existe pas déjà
        $existing = $this->redirectRepository->findActiveBySource($sourcePath);
        if ($existing) {
            throw new \InvalidArgumentException("Une redirection existe déjà pour '$sourcePath'.");
        }

        $redirect = new Redirect();
        $redirect->setSourcePath($sourcePath);
        $redirect->setTargetPath($targetPath);
        $redirect->setType($type);
        $redirect->setReason($reason);
        $redirect->setIsActive(true);

        $this->entityManager->persist($redirect);
        $this->entityManager->flush();

        return $redirect;
    }

    public function findRedirect(string $sourcePath): ?Redirect
    {
        return $this->redirectRepository->findActiveBySource($sourcePath);
    }

    public function deactivateRedirect(Redirect $redirect): void
    {
        $redirect->setIsActive(false);
        $redirect->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function ensureUniqueSlug(string $slug, string $entityType, Uuid $entityId): string
    {
        if (!$this->slugRegistry->slugExists($slug, $entityId)) {
            return $slug;
        }

        // Générer un slug unique en ajoutant un suffixe
        $counter = 1;
        $baseSlug = $slug;

        while ($this->slugRegistry->slugExists("$baseSlug-$counter", $entityId)) {
            ++$counter;
        }

        return "$baseSlug-$counter";
    }
}
