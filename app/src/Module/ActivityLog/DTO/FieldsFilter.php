<?php

namespace App\Module\ActivityLog\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class FieldsFilter
{
    /**
     * @param FieldFilter[] $fields
     */
    public function __construct(
        #[Assert\Valid]
        public readonly array $fields = [],
    ) {
    }
}
