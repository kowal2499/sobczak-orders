<?php

namespace App\Module\Production\Service\ProductionDateStrategy;

use App\Entity\AgreementLine;

class ProductionDateStrategyResolver
{
    public function __construct(
        private readonly CascadeStrategy $cascadeStrategy,
        private readonly FastStrategy $fastStrategy,
    ) {
    }

    public function resolve(AgreementLine $agreementLine): ProductionDateStrategyInterface
    {
        return ((float) $agreementLine->getFactor()) > 0.0
            ? $this->cascadeStrategy
            : $this->fastStrategy;
    }

    /**
     * @return ProductionDateStrategyInterface[]
     */
    public function getAll(): array
    {
        return [$this->cascadeStrategy, $this->fastStrategy];
    }
}
