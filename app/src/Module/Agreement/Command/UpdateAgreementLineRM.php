<?php

namespace App\Module\Agreement\Command;

class UpdateAgreementLineRM
{
    public function __construct(
        private readonly int $agreementLineId,
        private readonly bool $flush = true,
    ) {
    }

    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }

    public function shouldFlush(): bool
    {
        return $this->flush;
    }
}
