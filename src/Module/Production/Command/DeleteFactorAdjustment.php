<?php

namespace App\Module\Production\Command;

/** @deprecated */
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
