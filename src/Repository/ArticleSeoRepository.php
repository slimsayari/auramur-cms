<?php

namespace App\Repository;

use App\Entity\ArticleSeo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleSeoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleSeo::class);
    }

    public function findBySlug(string $slug): ?ArticleSeo
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findByArticle(string $articleId): ?ArticleSeo
    {
        return $this->createQueryBuilder('as')
            ->where('as.article = :articleId')
            ->setParameter('articleId', $articleId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
