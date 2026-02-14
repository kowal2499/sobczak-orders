<?php

namespace App\Module\Production\DTO;

class FactorAdjustmentDTO
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $productionId,
        private readonly string $description,
        private readonly float $factor
    ) {}

    public function getId(): ?int
    {
        return $this->id;
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