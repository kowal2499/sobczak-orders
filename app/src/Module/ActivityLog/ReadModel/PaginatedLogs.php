<?php

namespace App\Module\ActivityLog\ReadModel;

final class PaginatedLogs
{
    /**
     * @param LogModel[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageSize,
    ) {
    }
}
