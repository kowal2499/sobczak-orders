<?php

namespace App\Module\Production\Command;

class DeleteFactorAdjustment
{
    public function __construct(
        private readonly int $factorAdjustId,
    ) {
    }

    public function getFactorAdjustId(): int
    {
        return $this->factorAdjustId;
    }
}
