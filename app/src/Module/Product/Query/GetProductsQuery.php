<?php

namespace App\Module\Product\Query;

use Symfony\Component\Validator\Constraints as Assert;

class GetProductsQuery
{
    public function __construct(
        public readonly ?string $search,
        #[Assert\Positive]
        public readonly int $page,
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $pageSize,
    ) {
    }
}
