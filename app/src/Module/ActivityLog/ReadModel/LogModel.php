<?php

namespace App\Module\ActivityLog\ReadModel;

use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;

final class LogModel
{
    /**
     * @param LogFieldReadModel[]       $fields
     * @param array<string, mixed>|null $contentParams
     */
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly string $content,
        public readonly \DateTimeInterface $date,
        public readonly ?LogUserReadModel $user,
        public readonly LogLevel $level,
        public readonly LogPriority $priority,
        public readonly array $fields,
        public readonly ?array $contentParams = null,
    ) {
    }
}
