<?php

namespace App\Repository;

use App\Entity\ProductVariant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductVariant::class);
    }

    public function findBySku(string $sku): ?ProductVariant
    {
        return $this->findOneBy(['sku' => $sku]);
    }

    public function findActiveByProduct(string $productId): array
    {
        return $this->createQueryBuilder('pv')
            ->where('pv.product = :productId')
            ->andWhere('pv.isActive = true')
            ->setParameter('productId', $productId)
            ->orderBy('pv.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
