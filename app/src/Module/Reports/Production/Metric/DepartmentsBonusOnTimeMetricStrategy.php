<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\AgreementLine;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

/**
 * Miernik "Departments Bonus — w terminie". Jak DepartmentsBonusMetricStrategy (produkcje działów
 * domyślnych ukończone w zakresie miesiąca, współczynnik factorBonus), ale dodatkowo oznacza
 * terminowość: onTime=true tylko gdy completedAt mieści się w zaplanowanym oknie [dateStart, dateEnd].
 *
 * Rekordy poza oknem NIE są odfiltrowywane (kwalifikacja bez zmian) — trafiają do wyniku z onTime=false,
 * aby front mógł je pokazać wyszarzone (0 pkt). Brak zaplanowanego okna (null start/end) => onTime=false.
 * Agregat firmowy (bez filtra ROLE_CUSTOMER).
 */
class DepartmentsBonusOnTimeMetricStrategy extends AbstractProductionRecordStrategy
{
    public function getName(): string
    {
        return 'departments_bonus_on_time';
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

    protected function emitsOutOfRange(): bool
    {
        return true;
    }

    protected function isOnTime(ProductionRM $production, \DateTime $rangeStart, \DateTime $rangeEnd): bool
    {
        $dateStart = $production->getDateStart();
        $dateEnd = $production->getDateEnd();
        $completedAt = $production->getCompletedAt();

        if ($dateStart === null || $dateEnd === null || $completedAt === null) {
            return false;
        }

        $windowStart = new \DateTime($dateStart->format('Y-m-d') . ' 00:00:00');
        $windowEnd = new \DateTime($dateEnd->format('Y-m-d') . ' 23:59:59');

        return $completedAt >= $windowStart && $completedAt <= $windowEnd;
    }

    protected function factorsOf(ProductionRM $production): ?AssembledFactorDTO
    {
        return $production->getFactorBonus();
    }
}
