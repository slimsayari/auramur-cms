<?php

namespace App\Controller\Api;

use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    #[Route('', name: 'api_orders_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $customer = $this->getUser();
        $orders = $this->orderService->getCustomerOrders($customer);

        $data = array_map(function ($order) {
            return [
                'id' => $order->getId(),
                'reference' => $order->getReference(),
                'status' => $order->getStatus()->value,
                'totalAmount' => $order->getTotalAmount(),
                'currency' => $order->getCurrency(),
                'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
                'itemsCount' => $order->getItems()->count()
            ];
        }, $orders);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_orders_detail', methods: ['GET'])]
    public function detail(string $id): JsonResponse
    {
        $customer = $this->getUser();
        $orders = $this->orderService->getCustomerOrders($customer);

        // Find order by ID
        $order = null;
        foreach ($orders as $o) {
            if ((string) $o->getId() === $id) {
                $order = $o;
                break;
            }
        }

        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        $items = array_map(function ($item) {
            return [
                'id' => $item->getId(),
                'productId' => $item->getProductId(),
                'productName' => $item->getProductName(),
                'variantLabel' => $item->getVariantLabel(),
                'quantity' => $item->getQuantity(),
                'unitPrice' => $item->getUnitPrice(),
                'totalPrice' => $item->getTotalPrice()
            ];
        }, $order->getItems()->toArray());

        return $this->json([
            'id' => $order->getId(),
            'reference' => $order->getReference(),
            'status' => $order->getStatus()->value,
            'totalAmount' => $order->getTotalAmount(),
            'currency' => $order->getCurrency(),
            'paymentProvider' => $order->getPaymentProvider()->value,
            'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            'items' => $items
        ]);
    }
}
