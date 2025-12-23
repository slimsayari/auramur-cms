<?php

namespace App\Repository;

use App\Entity\WebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookEvent::class);
    }

    public function findPending(int $limit = 100): array
    {
        return $this->createQueryBuilder('we')
            ->where('we.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('we.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByEvent(string $event, string $status = null): array
    {
        $qb = $this->createQueryBuilder('we')
            ->where('we.event = :event')
            ->setParameter('event', $event);

        if ($status) {
            $qb->andWhere('we.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->orderBy('we.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFailed(): array
    {
        return $this->findBy(['status' => 'failed'], ['createdAt' => 'DESC']);
    }

    public function deleteOldDelivered(\DateTimeImmutable $before): int
    {
        return $this->createQueryBuilder('we')
            ->delete()
            ->where('we.status = :status')
            ->andWhere('we.deliveredAt < :before')
            ->setParameter('status', 'delivered')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }
}
