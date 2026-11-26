<?php

namespace App\Module\Reports\Schedule\DTO;

use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\ProductionRM;

/**
 * Only what the capacity sidebar (AgreementLineRmShowcaseItem) and its search read; the full read model row
 * made the capacity response weigh megabytes.
 */
final class ScheduleAgreementLineView
{
    /**
     * @return array<string, mixed>
     */
    public static function fromReadModel(AgreementLineRM $line): array
    {
        return [
            'agreementLineId' => $line->getAgreementLineId(),
            'orderNumber' => $line->getOrderNumber(),
            'internalNumber' => $line->getInternalNumber(),
            'customerName' => $line->getCustomerName(),
            'productName' => $line->getProductName(),
            'status' => $line->getStatus(),
            'agreementCreateDate' => $line->getAgreementCreateDate()->format('Y-m-d'),
            'confirmedDate' => $line->getConfirmedDate()->format('Y-m-d'),
            'factor' => $line->getFactor(),
            'userName' => $line->getUserName(),
            'q' => $line->getQ(),
            'productions' => array_map(self::production(...), $line->getProductions()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function production(ProductionRM $production): array
    {
        return [
            'id' => $production->getId(),
            'departmentSlug' => $production->getDepartmentSlug(),
            'dateStart' => $production->getDateStart()?->format('Y-m-d H:i:s'),
            'dateEnd' => $production->getDateEnd()?->format('Y-m-d H:i:s'),
            'status' => $production->getStatus(),
            'completedAt' => $production->getCompletedAt()?->format('Y-m-d H:i:s'),
            'factorRatio' => $production->getFactorRatio()?->toArray(),
            'isGhost' => $production->isGhost(),
        ];
    }
}
