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
     * @return ProductionReportRecordDTO[]
     */
    public function compute(\DateTimeInterface $start, \DateTimeInterface $end, bool $includeGhost = false): array
    {
        $rangeStart = new \DateTime($start->format('Y-m-d') . ' 00:00:00');
        $rangeEnd = new \DateTime($end->format('Y-m-d') . ' 23:59:59');

        $defaultSlugs = array_flip(TaskTypes::getDefaultSlugs());
        $records = [];

        foreach ($this->fetchLines($this->buildSearch($start, $end, $includeGhost)) as $line) {
            foreach ($line->getProductions() as $production) {
                if (!isset($defaultSlugs[$production->getDepartmentSlug()])) {
                    continue;
                }
                if (!$includeGhost && $production->isGhost()) {
                    continue;
                }
                if (!$this->qualifies($production, $rangeStart, $rangeEnd)) {
                    continue;
                }
                $records[] = $this->toRecord($line, $production);
            }
        }

        return $records;
    }

    private function toRecord(AgreementLineRM $line, ProductionRM $production): ProductionReportRecordDTO
    {
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
            ),
            new AgreementDTO(
                $line->getOrderNumber(),
                $line->getConfirmedDate(),
            ),
            new CustomerDTO(
                $line->getCustomer()->getName(),
            ),
            $this->factorsOf($production),
            $production->isGhost(),
        );
    }
}
