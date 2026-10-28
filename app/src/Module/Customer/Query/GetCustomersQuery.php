<?php

namespace App\Module\Customer\Query;

use Symfony\Component\Validator\Constraints as Assert;

class GetCustomersQuery
{
    /**
     * @param int[]|null $customerIds null = no ownership restriction
     */
    public function __construct(
        public readonly ?string $search,
        #[Assert\Positive]
        public readonly int $page,
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $pageSize,
        public readonly ?array $customerIds = null,
    ) {
    }
}
