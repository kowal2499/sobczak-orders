<?php

namespace App\Module\BonusSettlement\Command;

use Symfony\Component\Validator\Constraints as Assert;

class ReopenBonusPeriodCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $periodId,
    ) {
    }
}
