<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\ProductionRM;

/**
 * Szczegóły miernika "Orders Pending" — linie rozpoczęte do końca zakresu i niezakończone.
 * Produkcja kwalifikuje się gdy ma status COMPLETED lub NOT_APPLICABLE. Liczony firmowo
 * (bez filtra ROLE_CUSTOMER), dolna granica zakresu pomijana — zgodnie z dotychczasowym zachowaniem.
 */
class OrdersPendingDetailsStrategy extends AbstractOrdersDetailsStrategy
{
    public function getName(): string
    {
        return 'orders_pending_details';
    }

    protected function fetchDetailLines(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
    {
        return $this->agreementLineRepo->findPendingDetailLines($end);
    }

    protected function productionQualifies(ProductionRM $production): bool
    {
        return in_array((string) $production->getStatus(), [
            (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            (string) TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE,
        ], true);
    }
}
