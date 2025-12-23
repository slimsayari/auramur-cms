<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Enum\ContentStatus;
use App\Service\TypesenseExporter;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::postUpdate)]
class TypesenseExportSubscriber
{
    public function __construct(
        private TypesenseExporter $exporter,
        private LoggerInterface $logger,
    ) {}

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        // Seulement pour les produits
        if (!$entity instanceof Product) {
            return;
        }

        // Vérifier si le statut a changé vers PUBLISHED
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        
        if (isset($changeSet['status'])) {
            [$oldStatus, $newStatus] = $changeSet['status'];
            
            // Export automatique quand un produit est publié
            if ($newStatus === ContentStatus::PUBLISHED && $oldStatus !== ContentStatus::PUBLISHED) {
                try {
                    $this->exporter->exportProduct($entity);
                    $this->logger->info('Produit exporté automatiquement vers Typesense', [
                        'product_id' => $entity->getId(),
                        'product_name' => $entity->getName(),
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de l\'export automatique vers Typesense', [
                        'product_id' => $entity->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
