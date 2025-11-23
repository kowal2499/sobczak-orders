<?php

namespace App\Modules\Reports\Production\DTO;

class TaskCompletedRecordDTO
{
    public function __construct(
        private readonly string              $departmentSlug = '',
        private readonly int|float           $factor = 0,
        private readonly ?\DateTimeInterface $completedAt = null,
        private readonly ?AgreementLineDTO   $agreementLine = null,
        private readonly ?AgreementDTO       $agreement = null,
        private readonly ?CustomerDTO        $customer = null
    ) {}

    public function getDepartmentSlug(): string
    {
        return $this->departmentSlug;
    }

    public function getFactor(): int|float
    {
        return $this->factor;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function getAgreementLine(): ?AgreementLineDTO
    {
        return $this->agreementLine;
    }

    public function getAgreement(): ?AgreementDTO
    {
        return $this->agreement;
    }

    public function getCustomer(): ?CustomerDTO
    {
        return $this->customer;
    }
}
