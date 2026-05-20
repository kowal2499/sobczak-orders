<?php

namespace App\Module\ActivityLog\ReadModel;

final class LogFieldReadModel
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
    ) {
    }
}
