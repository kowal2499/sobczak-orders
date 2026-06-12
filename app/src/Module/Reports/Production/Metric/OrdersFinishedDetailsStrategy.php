<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\ProductionRM;

/**
 * Szczegóły miernika "Orders Finished" — linie zakończone w zakresie (i wcześniej rozpoczęte).
 * Produkcja kwalifikuje się gdy ma status COMPLETED. Dla ROLE_CUSTOMER z przypisanymi klientami
 * wynik ograniczony do tych klientów (dane wyświetlane filtrowane wg własności).
 */
class OrdersFinishedDetailsStrategy extends AbstractOrdersDetailsStrategy
{
    public function getName(): string
    {
        return 'orders_finished_details';
    }

    protected function fetchDetailLines(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
    {
        return $this->agreementLineRepo->findFinishedDetailLines($start, $end, $this->ownedCustomerIds());
    }

    protected function productionQualifies(ProductionRM $production): bool
    {
        return (string) $production->getStatus() === (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED;
    }
}
