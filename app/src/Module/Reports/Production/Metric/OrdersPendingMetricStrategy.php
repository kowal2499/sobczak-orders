<?php

namespace App\Module\Reports\Production\Metric;

/**
 * Miernik "Orders Pending" - agregat (suma factor + liczność) linii rozpoczętych do końca
 * zakresu i jeszcze niezakończonych. Dla ROLE_CUSTOMER ograniczony do przypisanych klientów,
 * tak jak "Orders Finished", żeby licznik zgadzał się ze szczegółami. Dolna granica zakresu jest pomijana.
 */
class OrdersPendingMetricStrategy extends AbstractMetricStrategy
{
    public function getName(): string
    {
        return 'orders_pending';
    }

    public function compute(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end,
        bool $includeGhost = false,
        array $options = []
    ): array {
        return $this->agreementLineRepo->getPendingSummary($end, $this->ownedCustomerIds());
    }
}
