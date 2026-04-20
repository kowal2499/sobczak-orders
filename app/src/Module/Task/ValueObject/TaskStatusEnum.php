<?php

namespace App\Module\Task\ValueObject;

enum TaskStatusEnum: int
{
    case AWAITS = 10;
    case PENDING = 11;
    case COMPLETED = 12;

    public function getName(): string
    {
        return match ($this) {
            self::AWAITS => 'Oczekuje',
            self::PENDING => 'W trakcie',
            self::COMPLETED => 'Zakończone',
        };
    }

    public static function getAllStatuses(): array
    {
        return array_map(fn(self $status) => [
            'value' => $status->value,
            'name' => $status->getName(),
        ], self::cases());
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }
}
