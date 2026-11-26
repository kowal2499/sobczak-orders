<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

/**
 * Miernik "Departments Bonus" - produkcje działów domyślnych zakończone w zakresie
 * (isCompleted=1, completedAt w zakresie, nie-ghost). Współczynnik: factorBonus.
 * Dla ROLE_CUSTOMER rekordy zawężone do przypisanych klientów.
 *
 * Kwalifikacja po completedAt (a nie po datach dpt). search() zawęża linie w SQL do tych z produkcją
 * działu domyślnego ukończoną w zakresie; ostateczną regułę nadal stosuje qualifies().
 */
class DepartmentsBonusMetricStrategy extends AbstractProductionRecordStrategy
{
    public function getName(): string
    {
        return 'departments_bonus';
    }

    protected function buildSearch(\DateTimeInterface $start, \DateTimeInterface $end, bool $includeGhost): array
    {
        return [
            'statusNot' => [AgreementLine::STATUS_DELETED],
            'hasProduction' => true,
            'productionCompletedBetween' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'departments' => TaskTypes::getDefaultSlugs(),
            ],
        ];
    }

    protected function qualifies(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        if (true !== $production->isCompleted()) {
            return false;
        }
        $completedAt = $production->getCompletedAt();

        return $completedAt !== null && $completedAt >= $rangeStart && $completedAt <= $rangeEnd;
    }

    protected function factorsOf(ProductionRM $production): ?AssembledFactorDTO
    {
        return $production->getFactorBonus();
    }

    /**
     * Produkcje spoza zakresu trafiają do wyniku bez współczynnika, żeby dymek komórki mógł
     * odróżnić "dział pracował, ale w innym miesiącu" od "linia nie przechodzi przez ten dział"
     * (ta druga sytuacja nie ma rekordu w ogóle). Na sumy nie wpływa - rekordy bez współczynnika
     * są pomijane w agregacji.
     */
    protected function emitsOutOfRange(): bool
    {
        return true;
    }

    protected function filtersByOwnership(): bool
    {
        return true;
    }
}
