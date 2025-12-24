<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\Order;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Find orders by customer
     */
    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find orders by status
     */
    public function findByStatus(OrderStatus $status): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find order by payment reference
     */
    public function findByPaymentReference(string $paymentReference): ?Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.paymentReference = :paymentReference')
            ->setParameter('paymentReference', $paymentReference)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Generate unique order reference
     */
    public function generateUniqueReference(): string
    {
        $year = date('Y');
        $prefix = 'ORD-' . $year . '-';
        
        // Find the last order of the year
        $lastOrder = $this->createQueryBuilder('o')
            ->where('o.reference LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastOrder) {
            // Extract number from reference (e.g., ORD-2024-001 -> 001)
            $lastNumber = (int) substr($lastOrder->getReference(), -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 3, '0', STR_PAD_LEFT);
    }
}
