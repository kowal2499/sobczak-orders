<?php

namespace App\Module\Product\Controller;

use App\Controller\BaseController;
use App\Entity\Product;
use App\Module\Product\Command\CreateProductCommand;
use App\Module\Product\Command\UpdateProductCommand;
use App\Module\Product\DTO\ProductDTO;
use App\Module\Product\Query\GetProductsQuery;
use App\System\CommandBus;
use App\System\QueryBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/products')]
#[IsGranted('ROLE_PRODUCTS')]
class ProductController extends BaseController
{
    private const PAGE_SIZE = 12;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return $this->json($this->queryBus->query(new GetProductsQuery(
            trim((string) $request->query->get('q', '')) ?: null,
            max(1, $request->query->getInt('page', 1)),
            self::PAGE_SIZE,
        )));
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function single(Product $product): JsonResponse
    {
        return $this->json(ProductDTO::fromEntity($product));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->payload($request);

        try {
            $this->commandBus->dispatch(new CreateProductCommand(
                $data['name'],
                $data['factor'],
                $data['description'],
            ));
        } catch (ValidationFailedException $e) {
            return $this->violationResponse($e);
        }

        return $this->json(['success' => true], Response::HTTP_CREATED);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function update(Product $product, Request $request): JsonResponse
    {
        $data = $this->payload($request);

        try {
            $this->commandBus->dispatch(new UpdateProductCommand(
                $product->getId(),
                $data['name'],
                $data['factor'],
                $data['description'],
            ));
        } catch (ValidationFailedException $e) {
            return $this->violationResponse($e);
        }

        return $this->json(['success' => true]);
    }

    /**
     * @return array{name: string, factor: ?float, description: ?string}
     */
    private function payload(Request $request): array
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $description = trim((string) ($data['description'] ?? ''));

        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'factor' => is_numeric($data['factor'] ?? null) ? (float) $data['factor'] : null,
            'description' => $description !== '' ? $description : null,
        ];
    }

    private function violationResponse(ValidationFailedException $exception): JsonResponse
    {
        $violation = $exception->getViolations()->get(0);

        return $this->json([
            'error' => (string) $violation->getMessage(),
            'field' => $violation->getPropertyPath(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
