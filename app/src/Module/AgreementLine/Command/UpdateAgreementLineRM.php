<?php

namespace App\Module\AgreementLine\Command;

class UpdateAgreementLineRM
{
    public function __construct(
        private readonly int $agreementLineId,
    ){
    }

    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }
}