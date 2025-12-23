<?php

namespace App\Controller\Admin;

use App\DTO\ProductCreateDTO;
use App\DTO\ProductUpdateDTO;
use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use App\Service\ValidationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/products')]
class ProductController extends AbstractController
{
    public function __construct(
        private ProductService $productService,
        private ValidationService $validationService,
        private ProductRepository $productRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'admin_product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            $dto = $this->serializer->deserialize(
                json_encode($data),
                ProductCreateDTO::class,
                'json'
            );

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->json(['error' => (string) $errors], 422);
            }

            $product = $this->productService->createProduct($dto);

            return $this->json([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'status' => $product->getStatus()->value,
            ], 201);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'admin_product_update', methods: ['PATCH'])]
    public function update(Product $product, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $data = json_decode($request->getContent(), true);

            $dto = $this->serializer->deserialize(
                json_encode($data),
                ProductUpdateDTO::class,
                'json'
            );

            $errors = $this->validator->validate($dto);
            if (count($errors) > 0) {
                return $this->json(['error' => (string) $errors], 422);
            }

            $product = $this->productService->updateProduct($product, $dto);

            return $this->json([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'status' => $product->getStatus()->value,
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}/publish', name: 'admin_product_publish', methods: ['PATCH'])]
    public function publish(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $product = $this->productService->publishProduct($product);

            return $this->json([
                'id' => $product->getId(),
                'status' => $product->getStatus()->value,
                'publishedAt' => $product->getPublishedAt(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'admin_product_delete', methods: ['DELETE'])]
    public function delete(Product $product): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $this->productService->deleteProduct($product);

            return $this->json(null, 204);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
