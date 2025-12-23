<?php

namespace App\EventListener;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Product;
use App\Service\SlugService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Article::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Category::class)]
class SlugChangeListener
{
    public function __construct(
        private SlugService $slugService,
    ) {}

    public function preUpdate(Product|Article|Category $entity, PreUpdateEventArgs $event): void
    {
        // Vérifier si le slug a changé
        if ($event->hasChangedField('slug')) {
            $oldSlug = $event->getOldValue('slug');
            $newSlug = $event->getNewValue('slug');

            if ($oldSlug !== $newSlug && !empty($oldSlug)) {
                // Créer une redirection automatique
                $this->slugService->createRedirect($oldSlug, $newSlug, 301);
            }
        }
    }
}
