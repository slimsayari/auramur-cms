<?php

namespace App\Repository;

use App\Entity\WooImportLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WooImportLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WooImportLog::class);
    }

    public function findLatestImports(int $limit = 10): array
    {
        return $this->createQueryBuilder('wil')
            ->orderBy('wil.importedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['importedAt' => 'DESC']);
    }

    public function findSuccessfulImports(): array
    {
        return $this->findByStatus('success');
    }

    public function findFailedImports(): array
    {
        return $this->findByStatus('failed');
    }
}
