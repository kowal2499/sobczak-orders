<?php

namespace App\Module\Production\Command;

class DeleteFactorAdjust
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
