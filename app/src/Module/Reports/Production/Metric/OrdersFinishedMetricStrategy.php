<?php

namespace App\Module\Reports\Production\Metric;

/**
 * Miernik "Orders Finished" — agregat (suma factor + liczność) linii zakończonych w zakresie.
 * Dla ROLE_CUSTOMER z przypisanymi klientami wynik jest ograniczony do tych klientów
 * (dane wyświetlane filtrowane wg własności). Brak przypisanych klientów = brak filtra,
 * zgodnie z dotychczasowym zachowaniem.
 */
class OrdersFinishedMetricStrategy extends AbstractMetricStrategy
{
    public function getName(): string
    {
        return 'orders_finished';
    }

    public function compute(?\DateTimeInterface $start, ?\DateTimeInterface $end, bool $includeGhost = false): array
    {
        return $this->agreementLineRepo->getFinishedSummary($start, $end, $this->ownedCustomerIds());
    }
}
