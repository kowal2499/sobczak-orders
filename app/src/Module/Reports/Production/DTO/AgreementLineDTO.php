<?php

namespace App\Module\Reports\Production\DTO;

class AgreementLineDTO
{
    public function __construct(
        private readonly ?int $id = null,
        private readonly ?float $factor = null,
        private readonly ?string $productName = null,
        private readonly ?\DateTimeInterface $productionStartDate = null,
        private readonly ?\DateTimeInterface $productionCompletionDate = null,
        private readonly ?string $internalNumber = null,
        private readonly ?\DateTimeInterface $agreementCreateDate = null,
        private readonly ?string $userName = null,
        private readonly ?int $status = null,
    ) {
    }

    public function getAgreementCreateDate(): ?\DateTimeInterface
    {
        return $this->agreementCreateDate;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInternalNumber(): ?string
    {
        return $this->internalNumber;
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
