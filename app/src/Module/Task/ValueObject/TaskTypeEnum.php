<?php

namespace App\Module\Task\ValueObject;

enum TaskTypeEnum: string
{
    case TASK_CUSTOM = 'task_custom';
    case TASK_CONFIRM_REALIZATION_DATE = 'task_confirm_realization_date';

    public function getName(): string
    {
        return match ($this) {
            self::TASK_CUSTOM => 'Zadanie niestandardowe',
            self::TASK_CONFIRM_REALIZATION_DATE => 'Potwierdzenie daty realizacji',
        };
    }

    public static function getAllTypes(): array
    {
        return array_map(fn(self $type) => [
            'value' => $type->value,
            'name' => $type->getName(),
        ], self::cases());
    }
}
