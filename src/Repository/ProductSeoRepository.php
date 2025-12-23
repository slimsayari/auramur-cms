<?php

namespace App\Repository;

use App\Entity\ProductSeo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductSeoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductSeo::class);
    }

    public function findBySlug(string $slug): ?ProductSeo
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByProduct(string $productId): ?ProductSeo
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.product = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
