<?php

namespace App\Module\Production\ValueObject;

enum ProductionTaskStatus: int
{
    case AWAITS = 0;
    case STARTED = 1;
    case IN_PROGRESS = 2;
    case COMPLETED = 3;
    case NOT_APPLICABLE = 4;

    public function getName(): string
    {
        return match ($this) {
            self::AWAITS => 'Oczekuje',
            self::STARTED => 'Rozpoczęte',
            self::IN_PROGRESS => 'W trakcie',
            self::COMPLETED => 'Zakończone',
            self::NOT_APPLICABLE => 'Nie dotyczy',
        };
    }
}
