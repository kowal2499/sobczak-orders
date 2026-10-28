<?php

namespace App\Module\BonusSettlement\Query;

class GetBonusPeriodQuery
{
    public function __construct(
        public readonly int $periodId,
    ) {
    }
}
