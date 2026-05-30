<?php

namespace App\Module\ActivityLog\ReadModel;

final class LogCountByField
{
    public function __construct(
        public readonly string $value,
        public readonly int $count,
    ) {
    }
}
