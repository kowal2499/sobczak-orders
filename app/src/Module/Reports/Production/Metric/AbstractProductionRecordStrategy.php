<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Reports\Production\DTO\AgreementDTO;
use App\Module\Reports\Production\DTO\AgreementLineDTO;
use App\Module\Reports\Production\DTO\CustomerDTO;
use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;

/**
 * Baza mierników zwracających listę rekordów per produkcja (Capacity, Departments Bonus).
 * Wspólny przebieg: pobranie linii z read modelu, iteracja po produkcjach działów domyślnych
 * i mapowanie kwalifikujących się produkcji na ProductionReportRecordDTO. Współczynniki
 * (factorRatio/factorBonus) są już policzone w ProductionRM, więc nie wołamy FactorCalculator.
 */
abstract class AbstractProductionRecordStrategy extends AbstractMetricStrategy
{
    /**
     * Opcja compute() wyłączająca filtr klienta - dla konsumentów serwerowych liczących agregat
     * firmowy (rozliczenie premii), niezależnie od tego, kto wywołał przeliczenie.
     */
    public const OPTION_COMPANY_WIDE = 'companyWide';

    /**
     * @return array<string, mixed> kryteria dla AgreementLineRMRepository::search()
     */
    abstract protected function buildSearch(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        bool $includeGhost
    ): array;

    /**
     * Czy produkcja kwalifikuje się do miernika (filtr dat/kompletności specyficzny dla miernika).
     */
    abstract protected function qualifies(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool;

    /**
     * Źródło współczynnika dla rekordu (factorRatio lub factorBonus z ProductionRM).
     */
    abstract protected function factorsOf(ProductionRM $production): ?AssembledFactorDTO;

    /**
     * Czy produkcja została ukończona w terminie (w zaplanowanym oknie). Domyślnie zawsze true -
     * mierniki egzekwujące terminowość nadpisują tę metodę.
     */
    protected function isOnTime(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        return true;
    }

    /**
     * Czy produkcje niekwalifikujące się do zakresu mają trafić do wyniku jako rekordy
     * "poza zakresem" (bez współczynnika). Dotyczy wyłącznie linii, które i tak są w raporcie -
     * front wykorzystuje je do pokazania okna produkcji obok zakresu raportu.
     */
    protected function emitsOutOfRange(): bool
    {
        return false;
    }

    /**
     * Czy rekord został uznany za terminowy dopiero dzięki tolerancji (widełkom), a nie
     * mieszcząc się w samym zaplanowanym oknie. Front pokazuje to jako osobny stan.
     */
    protected function appliedTolerance(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        return false;
    }

    /**
     * Odchylenie od zaplanowanego okna w dniach roboczych (dodatnie = po terminie,
     * ujemne = przed terminem). Mierniki bez pojęcia terminowości zwracają 0.
     */
    protected function timelinessWorkingDays(ProductionRM $production): int
    {
        return 0;
    }

    /**
     * Czy rekordy mają być zawężone do klientów przypisanych użytkownikowi z ROLE_CUSTOMER.
     */
    protected function filtersByOwnership(): bool
    {
        return false;
    }

    /**
     * Alternatywa dla filtersByOwnership() dla mierników, których sumy muszą zostać firmowe:
     * linie cudzych klientów zostają w wyniku, ale bez danych identyfikujących zamówienie.
     */
    protected function masksForeignLines(): bool
    {
        return false;
    }

    /**
     * @return ProductionReportRecordDTO[]
     */
    public function compute(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end,
        bool $includeGhost = false,
        array $options = []
    ): array {
        $rangeStart = new \DateTime($start->format('Y-m-d') . ' 00:00:00');
        $rangeEnd = new \DateTime($end->format('Y-m-d') . ' 23:59:59');

        $defaultSlugs = array_flip(TaskTypes::getDefaultSlugs());
        $emitsOutOfRange = $this->emitsOutOfRange();
        $records = [];

        $search = $this->buildSearch($start, $end, $includeGhost);
        if ($this->filtersByOwnership() && empty($options[self::OPTION_COMPANY_WIDE])) {
            $search = $this->withOwnership($search);
        }
        $visibleCustomerIds = $this->masksForeignLines() ? $this->ownedCustomerIds() : null;

        foreach ($this->fetchLines($search) as $line) {
            $lineRecords = [];
            $outOfRange = [];

            foreach ($line->getProductions() as $production) {
                if (!isset($defaultSlugs[$production->getDepartmentSlug()])) {
                    continue;
                }
                if (!$includeGhost && $production->isGhost()) {
                    continue;
                }
                if (!$this->qualifies($production, $rangeStart, $rangeEnd)) {
                    if ($emitsOutOfRange) {
                        $outOfRange[] = $this->toRecord($line, $production, false, false);
                    }
                    continue;
                }
                $onTime = $this->isOnTime($production, $rangeStart, $rangeEnd);
                $withinTolerance = $onTime && $this->appliedTolerance($production, $rangeStart, $rangeEnd);
                $lineRecords[] = $this->toRecord(
                    $line,
                    $production,
                    $onTime,
                    true,
                    $withinTolerance,
                    $this->timelinessWorkingDays($production),
                );
            }

            if (!$lineRecords) {
                // linia bez ani jednej kwalifikującej produkcji nie trafia do raportu w ogóle
                continue;
            }

            if ($visibleCustomerIds !== null && !in_array($line->getCustomerId(), $visibleCustomerIds, true)) {
                $records = array_merge($records, array_map($this->masked(...), $lineRecords));
                continue;
            }

            $records = array_merge($records, $lineRecords, $outOfRange);
        }

        return $records;
    }

    private function masked(ProductionReportRecordDTO $record): ProductionReportRecordDTO
    {
        // factorsStack niesie opisy korekt, więc zostaje tylko sama wartość
        $factors = $record->getFactors();

        return new ProductionReportRecordDTO(
            departmentSlug: $record->getDepartmentSlug(),
            dateStart: $record->getDateStart(),
            dateEnd: $record->getDateEnd(),
            status: $record->getStatus(),
            completedAt: $record->getCompletedAt(),
            factors: $factors !== null ? new AssembledFactorDTO($factors->factor) : null,
            isGhost: $record->getIsGhost(),
            onTime: $record->getOnTime(),
            inRange: $record->getInRange(),
        );
    }

    private function toRecord(
        AgreementLineRM $line,
        ProductionRM $production,
        bool $onTime = true,
        bool $inRange = true,
        bool $withinTolerance = false,
        int $timelinessWorkingDays = 0
    ): ProductionReportRecordDTO {
        return new ProductionReportRecordDTO(
            $production->getDepartmentSlug(),
            $production->getDateStart(),
            $production->getDateEnd(),
            $production->getStatus(),
            $production->getCompletedAt(),
            new AgreementLineDTO(
                $line->getAgreementLineId(),
                $line->getFactor(),
                $line->getProductName(),
                $line->getProductionStartDate(),
                $line->getProductionEndDate(),
                $line->getInternalNumber(),
            ),
            new AgreementDTO(
                $line->getOrderNumber(),
                $line->getConfirmedDate(),
            ),
            new CustomerDTO(
                $line->getCustomer()->getName(),
            ),
            $inRange ? $this->factorsOf($production) : null,
            $production->isGhost(),
            $onTime,
            $inRange,
            $withinTolerance,
            $timelinessWorkingDays,
        );
    }
}
