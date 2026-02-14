<?php

namespace App\Module\AgreementLine\Event;

class AgreementLineWasCreatedEvent
{
    public function __construct(
        private readonly int $agreementLineId,
    ) {
    }

    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }
}
