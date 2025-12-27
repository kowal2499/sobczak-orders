<?php

namespace App\Module\Production\Command;

use App\Module\Production\DTO\FactorRatioDTO;

class UpdateFactorRatioCommand
{
    public function __construct(
        private readonly int $agreementLineId,
        private readonly FactorRatioDTO $ratioDTO
    ) {
    }

    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }

    public function getRatioDTO(): FactorRatioDTO
    {
        return $this->ratioDTO;
    }


}