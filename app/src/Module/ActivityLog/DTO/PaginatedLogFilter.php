<?php

namespace App\Module\ActivityLog\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class PaginatedLogFilter
{
    /**
     * @param FieldFilter[] $fields
     */
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,
        #[Assert\Range(min: 1, max: 500)]
        public readonly int $pageSize = 50,
        #[Assert\Valid]
        public readonly array $fields = [],
        #[Assert\Length(max: 255)]
        public readonly ?string $filterBy = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $typePrefix = null,
    ) {
    }
}
