<?php

namespace App\Module\Reports\Production\Metric;

/**
 * Miernik "Orders Pending" — agregat (suma factor + liczność) linii rozpoczętych do końca
 * zakresu i jeszcze niezakończonych. Liczony firmowo (bez filtra ROLE_CUSTOMER), zgodnie
 * z dotychczasowym zachowaniem. Dolna granica zakresu jest pomijana.
 */
class OrdersPendingMetricStrategy extends AbstractMetricStrategy
{
    public function getName(): string
    {
        return 'orders_pending';
    }

    public function compute(?\DateTimeInterface $start, ?\DateTimeInterface $end, bool $includeGhost = false): array
    {
        return $this->agreementLineRepo->getPendingSummary($end);
    }
}
