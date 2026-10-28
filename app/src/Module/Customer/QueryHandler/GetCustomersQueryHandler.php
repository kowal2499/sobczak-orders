<?php

namespace App\Module\Customer\QueryHandler;

use App\Entity\Customer;
use App\Module\Customer\DTO\CustomerDTO;
use App\Module\Customer\Query\GetCustomersQuery;
use App\Repository\CustomerRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class GetCustomersQueryHandler
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    /**
     * @return array{items: CustomerDTO[], total: int, page: int, pageSize: int}
     */
    public function __invoke(GetCustomersQuery $query): array
    {
        $search = array_filter([
            'q' => $query->search,
            'ids' => $query->customerIds,
        ], fn ($value) => $value !== null);

        $doctrineQuery = $this->customerRepository->getWithSearch($search)
            ->setFirstResult(($query->page - 1) * $query->pageSize)
            ->setMaxResults($query->pageSize);

        $paginator = new Paginator($doctrineQuery);

        return [
            'items' => array_map(
                fn (Customer $customer) => CustomerDTO::fromEntity($customer),
                iterator_to_array($paginator)
            ),
            'total' => count($paginator),
            'page' => $query->page,
            'pageSize' => $query->pageSize,
        ];
    }
}
