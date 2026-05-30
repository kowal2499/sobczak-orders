<?php

namespace App\Module\ActivityLog\Query;

use App\Module\ActivityLog\DTO\FieldsFilter;

final class CountLogsByFieldQuery
{
    public function __construct(
        public readonly string $type,
        public readonly string $groupBy,
        public readonly FieldsFilter $filters,
    ) {
    }
}
