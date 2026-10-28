<?php

namespace App\Module\BonusSettlement\Command;

use Symfony\Component\Validator\Constraints as Assert;

class RecalculateBonusPeriodCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $periodId,
        /**
         * Brak wartości oznacza "zostaw widełki zapisane na okresie". Górna granica jest
         * ta sama, którą sanityzuje miernik.
         */
        #[Assert\Range(min: 0, max: 30)]
        public readonly ?int $toleranceDays = null,
    ) {
    }
}
