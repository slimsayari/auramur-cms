<?php

namespace App\Repository;

use App\Entity\PreviewToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PreviewTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PreviewToken::class);
    }

    public function findValidByToken(string $token): ?PreviewToken
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('pt')
            ->where('pt.token = :token')
            ->andWhere('pt.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteExpired(): int
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('pt')
            ->delete()
            ->where('pt.expiresAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }
}
