<?php

namespace App\Controller\Api;

use App\DTO\CustomerLoginDTO;
use App\DTO\CustomerRegistrationDTO;
use App\Service\CustomerAuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/customers')]
class CustomerController extends AbstractController
{
    public function __construct(
        private CustomerAuthService $customerAuthService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    #[Route('/register', name: 'api_customer_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                CustomerRegistrationDTO::class,
                'json'
            );

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $customer = $this->customerAuthService->register($dto);

            // Generate JWT token
            $token = $this->jwtManager->create($customer);

            return $this->json([
                'message' => 'Customer registered successfully',
                'customer' => [
                    'id' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'firstName' => $customer->getFirstName(),
                    'lastName' => $customer->getLastName(),
                ],
                'token' => $token
            ], Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login', name: 'api_customer_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                CustomerLoginDTO::class,
                'json'
            );

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $customer = $this->customerAuthService->authenticate($dto);

            // Generate JWT token
            $token = $this->jwtManager->create($customer);

            return $this->json([
                'message' => 'Login successful',
                'customer' => [
                    'id' => $customer->getId(),
                    'email' => $customer->getEmail(),
                    'firstName' => $customer->getFirstName(),
                    'lastName' => $customer->getLastName(),
                ],
                'token' => $token
            ]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/me', name: 'api_customer_profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        $customer = $this->getUser();

        return $this->json([
            'id' => $customer->getId(),
            'email' => $customer->getEmail(),
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'createdAt' => $customer->getCreatedAt()->format('Y-m-d H:i:s'),
            'lastLoginAt' => $customer->getLastLoginAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/me', name: 'api_customer_update_profile', methods: ['PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $customer = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $updatedCustomer = $this->customerAuthService->updateProfile($customer, $data);

        return $this->json([
            'message' => 'Profile updated successfully',
            'customer' => [
                'id' => $updatedCustomer->getId(),
                'email' => $updatedCustomer->getEmail(),
                'firstName' => $updatedCustomer->getFirstName(),
                'lastName' => $updatedCustomer->getLastName(),
            ]
        ]);
    }

    #[Route('/me', name: 'api_customer_delete_account', methods: ['DELETE'])]
    public function deleteAccount(): JsonResponse
    {
        $customer = $this->getUser();
        $this->customerAuthService->deleteAccount($customer);

        return $this->json(['message' => 'Account deleted successfully']);
    }
}
