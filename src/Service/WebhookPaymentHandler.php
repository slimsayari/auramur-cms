<?php

namespace App\Service;

use App\Entity\Customer;
use App\Enum\PaymentProvider;
use App\Repository\CustomerRepository;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class WebhookPaymentHandler
{
    public function __construct(
        private OrderService $orderService,
        private CustomerRepository $customerRepository,
        private LoggerInterface $logger,
        private string $stripeWebhookSecret
    ) {
    }

    /**
     * Handle Stripe webhook
     */
    public function handleStripeWebhook(string $payload, string $signature): ?array
    {
        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Invalid signature');
        }

        // Handle payment_intent.succeeded event
        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            
            // Extract customer and items from metadata
            $metadata = $paymentIntent->metadata;
            $customerEmail = $metadata->customer_email ?? null;
            $itemsJson = $metadata->items ?? null;

            if (!$customerEmail || !$itemsJson) {
                $this->logger->error('Missing metadata in Stripe webhook', ['payment_intent_id' => $paymentIntent->id]);
                throw new \RuntimeException('Missing metadata');
            }

            $customer = $this->customerRepository->findActiveByEmail($customerEmail);
            if (!$customer) {
                $this->logger->error('Customer not found', ['email' => $customerEmail]);
                throw new \RuntimeException('Customer not found');
            }

            $items = json_decode($itemsJson, true);
            $totalAmount = $paymentIntent->amount / 100; // Convert cents to euros
            $currency = strtoupper($paymentIntent->currency);

            $order = $this->orderService->createOrderFromWebhook(
                $customer,
                $items,
                (string) $totalAmount,
                $currency,
                PaymentProvider::STRIPE,
                $paymentIntent->id
            );

            $this->logger->info('Order created from Stripe webhook', [
                'order_id' => $order->getId(),
                'payment_intent_id' => $paymentIntent->id
            ]);

            return [
                'order_id' => $order->getId(),
                'reference' => $order->getReference()
            ];
        }

        return null;
    }

    /**
     * Handle PayPal webhook
     */
    public function handlePayPalWebhook(array $data): ?array
    {
        // PayPal webhook verification would go here
        // For now, we'll implement a basic version

        if ($data['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $data['resource'];
            
            // Extract customer and items from custom_id or purchase_units
            $customId = $resource['custom_id'] ?? null;
            if (!$customId) {
                $this->logger->error('Missing custom_id in PayPal webhook');
                throw new \RuntimeException('Missing custom_id');
            }

            $customData = json_decode($customId, true);
            $customerEmail = $customData['customer_email'] ?? null;
            $items = $customData['items'] ?? null;

            if (!$customerEmail || !$items) {
                $this->logger->error('Missing data in PayPal custom_id');
                throw new \RuntimeException('Missing data');
            }

            $customer = $this->customerRepository->findActiveByEmail($customerEmail);
            if (!$customer) {
                $this->logger->error('Customer not found', ['email' => $customerEmail]);
                throw new \RuntimeException('Customer not found');
            }

            $totalAmount = $resource['amount']['value'];
            $currency = $resource['amount']['currency_code'];

            $order = $this->orderService->createOrderFromWebhook(
                $customer,
                $items,
                $totalAmount,
                $currency,
                PaymentProvider::PAYPAL,
                $resource['id']
            );

            $this->logger->info('Order created from PayPal webhook', [
                'order_id' => $order->getId(),
                'transaction_id' => $resource['id']
            ]);

            return [
                'order_id' => $order->getId(),
                'reference' => $order->getReference()
            ];
        }

        return null;
    }
}
