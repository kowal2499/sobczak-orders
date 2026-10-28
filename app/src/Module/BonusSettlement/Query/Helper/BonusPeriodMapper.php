<?php

namespace App\Module\BonusSettlement\Query\Helper;

use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;

/**
 * Jedno miejsce zamieniające encje na odpowiedź API, wspólne dla listy i szczegółów okresu.
 */
class BonusPeriodMapper
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @return array<string, mixed>
     */
    public function toListItem(BonusPeriod $period): array
    {
        return [
            'id' => $period->getId(),
            'year' => $period->getYear(),
            'month' => $period->getMonth(),
            'status' => $period->getStatus()->value,
            'toleranceDays' => $period->getToleranceDays(),
            'calculatedAt' => $period->getCalculatedAt()?->format(self::DATE_FORMAT),
            'closedAt' => $period->getClosedAt()?->format(self::DATE_FORMAT),
            'closedByLabel' => $period->getClosedBy()?->getUserFullName(),
        ];
    }

    /**
     * @param BonusPeriodEntry[] $entries
     * @return array<string, mixed>
     */
    public function toDetail(BonusPeriod $period, array $entries): array
    {
        $rows = array_map(fn (BonusPeriodEntry $entry) => $this->toEntry($entry), $entries);

        return $this->toListItem($period) + [
            'entries' => $rows,
            'totalCalculated' => round(array_sum(array_column($rows, 'factorsCalculated')), 2),
            'totalEffective' => round(array_sum(array_column($rows, 'factorsEffective')), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toEntry(BonusPeriodEntry $entry): array
    {
        return [
            'id' => $entry->getId(),
            'userId' => $entry->getUser()->getId(),
            'userLabel' => $entry->getUserLabel(),
            'departmentSlug' => $entry->getDepartmentSlug(),
            'departmentLabel' => $entry->getDepartmentLabel(),
            'factorsCalculated' => $entry->getFactorsCalculated(),
            'factorsAdjusted' => $entry->getFactorsAdjusted(),
            'factorsEffective' => $entry->getEffectiveFactors(),
            'note' => $entry->getNote(),
            'adjustedAt' => $entry->getAdjustedAt()?->format(self::DATE_FORMAT),
            'adjustedAgainst' => $entry->getAdjustedAgainst(),
            // gotowy bool, żeby front nie powtarzał tej reguły po swojemu
            'adjustmentStale' => $entry->isAdjustmentStale(),
        ];
    }
}
