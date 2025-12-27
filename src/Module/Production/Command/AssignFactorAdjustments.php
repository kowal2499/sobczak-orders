<?php

namespace App\Module\Production\Command;

use App\Module\Production\DTO\FactorAdjustmentDTO;

/** @deprecated */
class AssignFactorAdjustments
{
    public function __construct(
        private readonly int $productionId,
        /** @var $factorAdjustments FactorAdjustmentDTO[] */
        private readonly array $factorAdjustments,
    ){
    }

    public function getProductionId(): int
    {
        return $this->productionId;
    }

    public function getFactorAdjustments(): array
    {
        return $this->factorAdjustments;
    }
}