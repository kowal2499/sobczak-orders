<?php

namespace App\Module\Product\QueryHandler;

use App\Entity\Product;
use App\Module\Product\DTO\ProductDTO;
use App\Module\Product\Query\GetProductsQuery;
use App\Repository\ProductRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class GetProductsQueryHandler
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    /**
     * @return array{items: ProductDTO[], total: int, page: int, pageSize: int}
     */
    public function __invoke(GetProductsQuery $query): array
    {
        $doctrineQuery = $this->productRepository->getWithSearch($query->search)
            ->setFirstResult(($query->page - 1) * $query->pageSize)
            ->setMaxResults($query->pageSize);

        $paginator = new Paginator($doctrineQuery);

        return [
            'items' => array_map(
                fn (Product $product) => ProductDTO::fromEntity($product),
                iterator_to_array($paginator)
            ),
            'total' => count($paginator),
            'page' => $query->page,
            'pageSize' => $query->pageSize,
        ];
    }
}
