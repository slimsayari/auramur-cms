<?php

namespace App\Service;

use App\DTO\CustomerLoginDTO;
use App\DTO\CustomerRegistrationDTO;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CustomerAuthService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerRepository $customerRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Register a new customer
     *
     * @throws \RuntimeException if email already exists
     */
    public function register(CustomerRegistrationDTO $dto): Customer
    {
        // Check if email already exists
        $existingCustomer = $this->customerRepository->findOneBy(['email' => $dto->email]);
        if ($existingCustomer) {
            throw new \RuntimeException('Email already exists');
        }

        $customer = new Customer();
        $customer->setEmail($dto->email);
        $customer->setFirstName($dto->firstName);
        $customer->setLastName($dto->lastName);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($customer, $dto->password);
        $customer->setPassword($hashedPassword);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        return $customer;
    }

    /**
     * Authenticate a customer
     *
     * @throws \RuntimeException if credentials are invalid
     */
    public function authenticate(CustomerLoginDTO $dto): Customer
    {
        $customer = $this->customerRepository->findActiveByEmail($dto->email);

        if (!$customer) {
            throw new \RuntimeException('Invalid credentials');
        }

        if (!$this->passwordHasher->isPasswordValid($customer, $dto->password)) {
            throw new \RuntimeException('Invalid credentials');
        }

        // Update last login
        $customer->setLastLoginAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $customer;
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Customer $customer, array $data): Customer
    {
        if (isset($data['firstName'])) {
            $customer->setFirstName($data['firstName']);
        }

        if (isset($data['lastName'])) {
            $customer->setLastName($data['lastName']);
        }

        $this->entityManager->flush();

        return $customer;
    }

    /**
     * Soft delete customer account
     */
    public function deleteAccount(Customer $customer): void
    {
        $customer->setDeletedAt(new \DateTimeImmutable());
        $customer->setIsActive(false);
        $this->entityManager->flush();
    }
}
