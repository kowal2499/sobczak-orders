<?php

namespace App\Module\Production\Command;

class UpdateFactorAdjust
{
    public function __construct(
        private readonly int    $factorAdjustId,
        private readonly string $description,
        private readonly float  $factor,
    ) {
    }

    public function getFactorAdjustId(): int
    {
        return $this->factorAdjustId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFactor(): float
    {
        return $this->factor;
    }
}
