<?php

namespace App\Repository;

use App\Entity\ContentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

class ContentVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentVersion::class);
    }

    public function findByEntity(string $entityType, Uuid $entityId): array
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.entityType = :entityType')
            ->andWhere('cv.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('cv.versionNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestVersion(string $entityType, Uuid $entityId): ?ContentVersion
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.entityType = :entityType')
            ->andWhere('cv.entityId = :entityId')
            ->setParameter('entityType', $entityType)
            ->setParameter('entityId', $entityId)
            ->orderBy('cv.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextVersionNumber(string $entityType, Uuid $entityId): int
    {
        $latest = $this->findLatestVersion($entityType, $entityId);
        return $latest ? $latest->getVersionNumber() + 1 : 1;
    }
}
