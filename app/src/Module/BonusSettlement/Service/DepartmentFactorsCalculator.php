<?php

namespace App\Module\BonusSettlement\Service;

use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;
use App\Module\Reports\Production\Provider\DashboardMetricProvider;

/**
 * Sumuje wsad premiowy per dział za zadany zakres, na podstawie miernika
 * "Ukończone zadania produkcyjne (w terminie)".
 *
 * Serwerowy odpowiednik reguły, która do tej pory żyła wyłącznie na froncie
 * (DepartmentMetricMixin.aggregateByDepartment). Miernik zwraca również rekordy spóźnione
 * i poza zakresem - to konsument decyduje, że premii nie naliczają. Rozjazd między tą klasą
 * a mixinem oznacza, że pulpit pokazuje co innego niż rozliczenie, dlatego reguła ma tu
 * własny test.
 */
class DepartmentFactorsCalculator
{
    public const METRIC = 'departments_bonus_on_time';

    public function __construct(
        private readonly DashboardMetricProvider $metrics,
    ) {
    }

    /**
     * @return array<string, float> slug działu => suma współczynników; wszystkie działy
     *                              produkcyjne są obecne, dział bez premii ma 0.0
     */
    public function forRange(\DateTimeInterface $from, \DateTimeInterface $to, int $toleranceDays): array
    {
        $sums = [];
        foreach (DepartmentEnum::getProductionDepartments() as $department) {
            $sums[$department->value] = 0.0;
        }

        $records = $this->metrics->getMetric(self::METRIC, $from, $to, false, [
            'toleranceDays' => $toleranceDays,
        ]);

        foreach ($records as $record) {
            if (!$this->countsTowardsBonus($record)) {
                continue;
            }
            $slug = $record->getDepartmentSlug();
            if (!array_key_exists($slug, $sums)) {
                continue;
            }
            $sums[$slug] += $record->getFactors()->factor;
        }

        return $sums;
    }

    /**
     * Warunek musi pozostać identyczny z frontowym:
     * assets/js-vue/src/modules/dashboard/components/Metrics/ProductionMetric/DepartmentMetricMixin.js
     */
    private function countsTowardsBonus(ProductionReportRecordDTO $record): bool
    {
        if (!$record->getInRange()) {
            return false;
        }
        if (!$record->getOnTime()) {
            return false;
        }
        if ($record->getIsGhost()) {
            return false;
        }

        return null !== $record->getFactors();
    }
}
