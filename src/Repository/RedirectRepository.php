<?php

namespace App\Repository;

use App\Entity\Redirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Redirect::class);
    }

    public function findActiveBySource(string $sourcePath): ?Redirect
    {
        return $this->createQueryBuilder('r')
            ->where('r.sourcePath = :sourcePath')
            ->andWhere('r.isActive = true')
            ->setParameter('sourcePath', $sourcePath)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllActive(): array
    {
        return $this->findBy(['isActive' => true], ['createdAt' => 'DESC']);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['createdAt' => 'DESC']);
    }
}
