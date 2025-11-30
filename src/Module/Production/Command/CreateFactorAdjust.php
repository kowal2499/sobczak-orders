<?php

namespace App\Module\Production\Command;

class CreateFactorAdjust
{
    public function __construct(
        private readonly int    $productionId,
        private readonly string $description,
        private readonly float  $factor,
    ) {
    }

    public function getProductionId(): int
    {
        return $this->productionId;
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