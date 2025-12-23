<?php

namespace App\Repository;

use App\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    public function findByEntity(string $entityType, Uuid $entityId, string $locale): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.entityType = :entityType')
            ->andWhere('t.entityId = :entityId')
            ->andWhere('t.locale = :locale')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult();
    }

    public function findByEntityAndField(string $entityType, Uuid $entityId, string $field, string $locale): ?Translation
    {
        return $this->createQueryBuilder('t')
            ->where('t.entityType = :entityType')
            ->andWhere('t.entityId = :entityId')
            ->andWhere('t.field = :field')
            ->andWhere('t.locale = :locale')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->setParameter('field', $field)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getAvailableLocales(string $entityType, Uuid $entityId): array
    {
        $results = $this->createQueryBuilder('t')
            ->select('DISTINCT t.locale')
            ->where('t.entityType = :entityType')
            ->andWhere('t.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->getQuery()
            ->getScalarResult();

        return array_column($results, 'locale');
    }
}
