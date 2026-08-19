<?php

namespace App\Module\BonusSettlement\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Okresy zakładane są ręcznie - rozliczeniu podlegają tylko te miesiące, które ktoś
 * świadomie otworzył. Przeliczenie nie zakłada brakującego miesiąca w locie.
 */
class CreateBonusPeriodCommand
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Range(min: 2000, max: 2100)]
        public readonly int $year,
        #[Assert\NotNull]
        #[Assert\Range(min: 1, max: 12)]
        public readonly int $month,
    ) {
    }
}
