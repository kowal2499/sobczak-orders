<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Production\Factor\DTO\FactorDTO;
use App\Module\Reports\Production\DTO\AgreementDTO;
use App\Module\Reports\Production\DTO\AgreementLineDTO;
use App\Module\Reports\Production\DTO\CustomerDTO;
use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;

/**
 * Baza mierników szczegółów (drill-down) "Orders Pending/Finished" opartych o read model.
 *
 * Odwzorowuje dotychczasowe zachowanie DoctrineProduction*Repository::getDetails() (leftJoin):
 *  - dla każdej linii spełniającej kryterium powstaje rekord per kwalifikująca się produkcja
 *    (dział domyślny, nie-ghost, status właściwy dla miernika);
 *  - linia bez pasującej produkcji daje JEDEN rekord z pustym działem;
 *  - pola dateStart/dateEnd/status oraz isGhost rekordu są puste (kolumny nieselekcjonowane
 *    w starym zapytaniu), completedAt pochodzi z produkcji;
 *  - współczynnik to factorRatio produkcji (już policzony w ProductionRM), a dla rekordu bez
 *    produkcji — bazowy współczynnik linii (odpowiednik RATIO bez korekt działowych).
 */
abstract class AbstractOrdersDetailsStrategy extends AbstractMetricStrategy
{
    /**
     * @return AgreementLineRM[]
     */
    abstract protected function fetchDetailLines(?\DateTimeInterface $start, ?\DateTimeInterface $end): array;

    /**
     * Czy produkcja kwalifikuje się do leftJoin (filtr statusu specyficzny dla miernika).
     */
    abstract protected function productionQualifies(ProductionRM $production): bool;

    /**
     * @return ProductionReportRecordDTO[]
     */
    public function compute(?\DateTimeInterface $start, ?\DateTimeInterface $end, bool $includeGhost = false): array
    {
        $defaultSlugs = array_flip(TaskTypes::getDefaultSlugs());
        $records = [];

        foreach ($this->fetchDetailLines($start, $end) as $line) {
            $matched = [];
            foreach ($line->getProductions() as $production) {
                if (!isset($defaultSlugs[$production->getDepartmentSlug()])) {
                    continue;
                }
                if ($production->isGhost()) {
                    continue;
                }
                if (!$this->productionQualifies($production)) {
                    continue;
                }
                $matched[] = $production;
            }

            if (empty($matched)) {
                $records[] = $this->toRecord($line, null);
                continue;
            }
            foreach ($matched as $production) {
                $records[] = $this->toRecord($line, $production);
            }
        }

        return $records;
    }

    private function toRecord(AgreementLineRM $line, ?ProductionRM $production): ProductionReportRecordDTO
    {
        return new ProductionReportRecordDTO(
            $production?->getDepartmentSlug() ?? '',
            null,
            null,
            null,
            $production?->getCompletedAt(),
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
            $production?->getFactorRatio() ?? $this->lineFactor($line),
            false,
        );
    }

    /**
     * Bazowy współczynnik linii (bez korekt działowych) — odpowiednik FACTOR_ADJUSTMENT_RATIO
     * liczonego dla rekordu bez przypisanej produkcji.
     */
    private function lineFactor(AgreementLineRM $line): AssembledFactorDTO
    {
        $factor = (float) $line->getFactor();

        return new AssembledFactorDTO($factor, [
            new FactorDTO(FactorSource::AGREEMENT_LINE, $factor, $line->getAgreementLineId(), null, null),
        ]);
    }
}
