<?php

namespace App\Module\Reports\Production\DTO;

class AgreementLineDTO
{
    public function __construct(
        private readonly ?int $id = null,
        private readonly ?float $factor = null,
        private readonly ?string $productName = null,
        private readonly ?\DateTimeInterface $productionStartDate = null,
        private readonly ?\DateTimeInterface $productionCompletionDate = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function getProductionStartDate(): ?\DateTimeInterface
    {
        return $this->productionStartDate;
    }

    public function getProductionCompletionDate(): ?\DateTimeInterface
    {
        return $this->productionCompletionDate;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }
}
