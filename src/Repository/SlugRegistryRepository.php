<?php

namespace App\Repository;

use App\Entity\SlugRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class SlugRegistryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlugRegistry::class);
    }

    public function findBySlug(string $slug): ?SlugRegistry
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByEntity(string $entityType, Uuid $entityId): ?SlugRegistry
    {
        return $this->createQueryBuilder('sr')
            ->where('sr.entityType = :entityType')
            ->andWhere('sr.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function slugExists(string $slug, ?Uuid $excludeEntityId = null): bool
    {
        $qb = $this->createQueryBuilder('sr')
            ->select('COUNT(sr.id)')
            ->where('sr.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeEntityId) {
            $qb->andWhere('sr.entityId != :excludeId')
                ->setParameter('excludeId', $excludeEntityId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
