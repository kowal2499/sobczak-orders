<?php

namespace App\Module\ActivityLog\Query;

use App\Module\ActivityLog\DTO\PaginatedLogFilter;

final class GetPaginatedLogsQuery
{
    public function __construct(
        public readonly ?string $type,
        public readonly PaginatedLogFilter $filter,
    ) {
    }
}
