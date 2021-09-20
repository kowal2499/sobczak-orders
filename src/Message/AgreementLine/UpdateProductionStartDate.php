<?php

namespace App\Message\AgreementLine;

class UpdateProductionStartDate
{
    private $agreementLineId;

    public function __construct(int $agreementLineId)
    {
        $this->agreementLineId = $agreementLineId;
    }

    /**
     * @return int
     */
    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }
}