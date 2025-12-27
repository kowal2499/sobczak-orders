<?php

namespace App\Module\Production\Command;

class DeleteFactorRatioCommand
{
    public function __construct(
        private readonly int $factorRatioId,
    ) {
    }

    public function getFactorRatioId(): int
    {
        return $this->factorRatioId;
    }
}