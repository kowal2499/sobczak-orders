<?php

namespace App\Message\AgreementLine;

class UpdateCompletionFlagCommand
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