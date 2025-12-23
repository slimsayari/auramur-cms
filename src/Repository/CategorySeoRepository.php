<?php

namespace App\Repository;

use App\Entity\CategorySeo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategorySeoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorySeo::class);
    }

    public function findBySlug(string $slug): ?CategorySeo
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByCategory(string $categoryId): ?CategorySeo
    {
        return $this->createQueryBuilder('cs')
            ->where('cs.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
