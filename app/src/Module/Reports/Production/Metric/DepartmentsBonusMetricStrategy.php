<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\AgreementLine;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

/**
 * Miernik "Departments Bonus" — produkcje działów domyślnych zakończone w zakresie
 * (isCompleted=1, completedAt w zakresie, nie-ghost). Współczynnik: factorBonus.
 * Agregat firmowy (bez filtra ROLE_CUSTOMER).
 *
 * Brak prefiltra dat w search() — kwalifikacja po completedAt (a nie po datach dpt),
 * dlatego pobieramy linie z produkcjami nie-ghost i filtrujemy completedAt w PHP.
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
}
