<?php

namespace App\Module\Agreement\Event;

class AgreementLineWasUpdatedEvent
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
