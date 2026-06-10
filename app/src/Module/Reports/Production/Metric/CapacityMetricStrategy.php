<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

/**
 * Miernik "Capacity" — produkcje działów domyślnych, których dateEnd mieści się w zakresie.
 * Współczynnik: factorRatio. Agregat firmowy (bez filtra ROLE_CUSTOMER).
 */
class CapacityMetricStrategy extends AbstractProductionRecordStrategy
{
    public function getName(): string
    {
        return 'capacity';
    }

    protected function buildSearch(\DateTimeInterface $start, \DateTimeInterface $end, bool $includeGhost): array
    {
        $search = [
            'statusNot' => [AgreementLine::STATUS_DELETED],
            'dptDateRange' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
                'departments' => TaskTypes::getDefaultSlugs(),
            ],
        ];
        $search[$includeGhost ? 'hasProductionIncludingGhost' : 'hasProduction'] = true;

        return $search;
    }

    protected function qualifies(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        $dateEnd = $production->getDateEnd();

        return $dateEnd !== null && $dateEnd >= $rangeStart && $dateEnd <= $rangeEnd;
    }

    protected function factorsOf(ProductionRM $production): ?AssembledFactorDTO
    {
        return $production->getFactorRatio();
    }
}
