<?php

namespace App\Repository;

use App\Entity\AiProviderConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiProviderConfig>
 */
class AiProviderConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiProviderConfig::class);
    }

    public function findActive(): ?AiProviderConfig
    {
        return $this->createQueryBuilder('a')
            ->where('a.isActive = :active')
            ->setParameter('active', true)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProvider(string $provider): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.provider = :provider')
            ->setParameter('provider', $provider)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
