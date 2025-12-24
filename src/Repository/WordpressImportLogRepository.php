<?php

namespace App\Repository;

use App\Entity\WordpressImportLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WordpressImportLog>
 */
class WordpressImportLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WordpressImportLog::class);
    }

    public function findLatest(int $limit = 10): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.importedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findSuccessful(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = :status')
            ->setParameter('status', 'success')
            ->orderBy('w.importedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFailed(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.status = :status')
            ->setParameter('status', 'failed')
            ->orderBy('w.importedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
