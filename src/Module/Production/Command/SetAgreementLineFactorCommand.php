<?php

namespace App\Module\Production\Command;

use App\Module\Production\DTO\FactorRatioDTO;

class SetAgreementLineFactorCommand
{
    public function __construct(
        private readonly int $agreementLineId,
        private readonly float $factorValue,
    ) {
    }

    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }

    public function getFactorValue(): float
    {
        return $this->factorValue;
    }

}