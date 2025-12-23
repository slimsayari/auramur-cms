<?php

namespace App\Repository;

use App\Entity\AiGeneration;
use App\Enum\ContentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AiGenerationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiGeneration::class);
    }

    public function findPendingValidation()
    {
        return $this->createQueryBuilder('ag')
            ->where('ag.status = :status')
            ->setParameter('status', ContentStatus::DRAFT)
            ->orderBy('ag.generatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(ContentStatus $status)
    {
        return $this->createQueryBuilder('ag')
            ->where('ag.status = :status')
            ->setParameter('status', $status)
            ->orderBy('ag.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
