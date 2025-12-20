<?php

namespace App\Modules\Reports\Production\DTO;

use App\Module\Production\Entity\FactorSource;

class FactorDTO
{
    public function __construct(
        private readonly FactorSource $source,
        private readonly string $id,
        private readonly int|float $value
    ) {
    }

    public function getSource(): FactorSource
    {
        return $this->source;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getValue(): float|int
    {
        return $this->value;
    }
}