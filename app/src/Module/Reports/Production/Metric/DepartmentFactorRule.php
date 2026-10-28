<?php

namespace App\Module\Reports\Production\Metric;

use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;

/**
 * Które rekordy miernika per produkcja wchodzą do sumy działu. Serwerowy odpowiednik
 * DepartmentMetricMixin.aggregateByDepartment() - korzystają z niego rozliczenie premii
 * i historia mierników, więc obie liczą to samo co kafel.
 */
final class DepartmentFactorRule
{
    public static function counts(ProductionReportRecordDTO $record): bool
    {
        return self::settles($record) && $record->getOnTime();
    }

    /**
     * Rekord rozliczany w zakresie, ale poza oknem terminowości - współczynnik, który przepadł.
     */
    public static function lostToDelay(ProductionReportRecordDTO $record): bool
    {
        return self::settles($record) && !$record->getOnTime();
    }

    private static function settles(ProductionReportRecordDTO $record): bool
    {
        return $record->getInRange()
            && !$record->getIsGhost()
            && null !== $record->getFactors();
    }
}
