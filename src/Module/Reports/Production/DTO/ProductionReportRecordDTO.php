<?php

namespace App\Modules\Reports\Production\DTO;

use App\Module\Production\Factor\DTO\AssembledFactorDTO;

class ProductionReportRecordDTO
{

    public function __construct(
        private readonly string              $departmentSlug = '',
        private readonly ?\DateTimeInterface $completedAt = null,
        private readonly ?AgreementLineDTO   $agreementLine = null,
        private readonly ?AgreementDTO       $agreement = null,
        private readonly ?CustomerDTO        $customer = null,
        private readonly ?AssembledFactorDTO $factors = null,
    ) {
    }

    public function getDepartmentSlug(): string
    {
        return $this->departmentSlug;
    }

    public function getFactors(): ?AssembledFactorDTO
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
}
