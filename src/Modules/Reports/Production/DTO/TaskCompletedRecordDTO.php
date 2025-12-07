<?php

namespace App\Modules\Reports\Production\DTO;

class TaskCompletedRecordDTO
{
    /** @var FactorDTO[] */
    private array $factors = [];

    public function __construct(
        private readonly string              $departmentSlug = '',
        private readonly ?\DateTimeInterface $completedAt = null,
        private readonly ?AgreementLineDTO   $agreementLine = null,
        private readonly ?AgreementDTO       $agreement = null,
        private readonly ?CustomerDTO        $customer = null
    ) {
    }

    public function getDepartmentSlug(): string
    {
        return $this->departmentSlug;
    }

    public function getFactors(): array
    {
        return $this->factors;
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

    public function setFactors(array $factors): void
    {
        $this->factors = $factors;
    }
}
