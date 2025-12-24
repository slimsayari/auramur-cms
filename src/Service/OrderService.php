<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\PaymentProvider;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderRepository $orderRepository
    ) {
    }

    /**
     * Create order from webhook data
     */
    public function createOrderFromWebhook(
        Customer $customer,
        array $items,
        string $totalAmount,
        string $currency,
        PaymentProvider $paymentProvider,
        string $paymentReference
    ): Order {
        $order = new Order();
        $order->setCustomer($customer);
        $order->setTotalAmount($totalAmount);
        $order->setCurrency($currency);
        $order->setPaymentProvider($paymentProvider);
        $order->setPaymentReference($paymentReference);
        
        // Generate unique reference
        $reference = $this->orderRepository->generateUniqueReference();
        $order->setReference($reference);

        // Add items
        foreach ($items as $itemData) {
            $orderItem = new OrderItem();
            $orderItem->setProductId(Uuid::fromString($itemData['product_id']));
            $orderItem->setProductName($itemData['product_name']);
            $orderItem->setVariantLabel($itemData['variant_label'] ?? null);
            $orderItem->setQuantity($itemData['quantity']);
            $orderItem->setUnitPrice($itemData['unit_price']);
            
            $order->addItem($orderItem);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * Get customer orders
     */
    public function getCustomerOrders(Customer $customer): array
    {
        return $this->orderRepository->findByCustomer($customer);
    }
}
