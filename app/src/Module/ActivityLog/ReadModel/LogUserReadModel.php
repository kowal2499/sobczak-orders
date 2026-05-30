<?php

namespace App\Module\ActivityLog\ReadModel;

final class LogUserReadModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
    ) {
    }
}
