<?php

namespace App\Module\Reports\Production\DTO;

use App\Module\Production\Factor\DTO\AssembledFactorDTO;

class ProductionReportRecordDTO
{
    public function __construct(
        private readonly string $departmentSlug = '',
        private readonly ?\DateTimeInterface $dateStart = null,
        private readonly ?\DateTimeInterface $dateEnd = null,
        private readonly ?string $status = null,
        private readonly ?\DateTimeInterface $completedAt = null,
        private readonly ?AgreementLineDTO $agreementLine = null,
        private readonly ?AgreementDTO $agreement = null,
        private readonly ?CustomerDTO $customer = null,
        private readonly ?AssembledFactorDTO $factors = null,
        private readonly bool $isGhost = false,
        private readonly bool $onTime = true,
        private readonly bool $inRange = true,
        private readonly bool $withinTolerance = false,
        private readonly int $timelinessWorkingDays = 0,
    ) {
    }

    /**
     * Czy rekord jest rozliczany w bieżącym zakresie raportu. Rekordy "poza zakresem"
     * (inRange=false) niosą tylko okno produkcji — bez współczynnika (factors=null).
     */
    public function getInRange(): bool
    {
        return $this->inRange;
    }

    /**
     * Czy premia należy się dopiero dzięki widełkom terminowości — ukończenie wypadło poza
     * zaplanowanym oknem, ale w granicach tolerancji.
     */
    public function getWithinTolerance(): bool
    {
        return $this->withinTolerance;
    }

    /**
     * Odchylenie od zaplanowanego okna w dniach roboczych: dodatnie = po terminie,
     * ujemne = przed terminem, 0 = ukończenie w oknie (albo brak dat).
     */
    public function getTimelinessWorkingDays(): int
    {
        return $this->timelinessWorkingDays;
    }

    public function getIsGhost(): bool
    {
        return $this->isGhost;
    }

    public function getOnTime(): bool
    {
        return $this->onTime;
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

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
