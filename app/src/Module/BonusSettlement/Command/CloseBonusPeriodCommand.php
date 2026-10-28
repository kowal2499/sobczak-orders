<?php

namespace App\Module\BonusSettlement\Command;

use Symfony\Component\Validator\Constraints as Assert;

class CloseBonusPeriodCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $periodId,
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly ?int $closedByUserId,
    ) {
    }
}
