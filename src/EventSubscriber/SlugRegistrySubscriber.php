<?php

namespace App\EventSubscriber;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\SlugRegistry;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class SlugRegistrySubscriber
{
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$this->isSluggableEntity($entity)) {
            return;
        }

        $this->registerSlug($entity, $args->getObjectManager());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$this->isSluggableEntity($entity)) {
            return;
        }

        // Si le slug a changé, mettre à jour le registre
        if ($args->hasChangedField('slug')) {
            $this->updateSlugRegistry($entity, $args->getOldValue('slug'), $args->getNewValue('slug'), $args->getObjectManager());
        }
    }

    private function isSluggableEntity(mixed $entity): bool
    {
        return $entity instanceof Product || $entity instanceof Article || $entity instanceof Category;
    }

    private function registerSlug(Product|Article|Category $entity, $entityManager): void
    {
        $slug = $entity->getSlug();
        if (empty($slug)) {
            return;
        }

        // Vérifier si le slug existe déjà dans le registre
        $repository = $entityManager->getRepository(SlugRegistry::class);
        $existing = $repository->findOneBy(['slug' => $slug]);

        if (!$existing) {
            $registry = new SlugRegistry();
            $registry->setSlug($slug);
            $registry->setEntityType(get_class($entity));
            $registry->setEntityId($entity->getId());

            $entityManager->persist($registry);
            $entityManager->flush();
        }
    }

    private function updateSlugRegistry(Product|Article|Category $entity, string $oldSlug, string $newSlug, $entityManager): void
    {
        $repository = $entityManager->getRepository(SlugRegistry::class);

        // Supprimer l'ancien enregistrement
        $oldRegistry = $repository->findOneBy(['slug' => $oldSlug, 'entityId' => $entity->getId()]);
        if ($oldRegistry) {
            $entityManager->remove($oldRegistry);
        }

        // Créer le nouvel enregistrement
        $newRegistry = new SlugRegistry();
        $newRegistry->setSlug($newSlug);
        $newRegistry->setEntityType(get_class($entity));
        $newRegistry->setEntityId($entity->getId());

        $entityManager->persist($newRegistry);
        $entityManager->flush();
    }
}
