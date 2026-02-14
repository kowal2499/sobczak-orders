<?php

namespace App\Module\Reports\Production\DTO;

class AgreementDTO
{
    public function __construct(
        private readonly ?string $orderNumber = null,
        private readonly ?\DateTimeInterface $confirmedDate = null
    ) {}

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getConfirmedDate(): ?\DateTimeInterface
    {
        return $this->confirmedDate;
    }
}
