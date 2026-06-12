<?php

namespace App\Module\Reports\Production\Metric;

use App\Entity\User;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use Symfony\Component\Security\Core\Security;

/**
 * Wspólna baza strategii mierników — dostęp do read modelu i danych użytkownika
 * oraz pomocnicze metody budowania kryteriów wyszukiwania.
 */
abstract class AbstractMetricStrategy implements MetricStrategyInterface
{
    public function __construct(
        protected readonly AgreementLineRMRepository $agreementLineRepo,
        protected readonly Security $security,
    ) {
    }

    /**
     * @param array<string, mixed> $search klucze obsługiwane przez AgreementLineRMRepository::search()
     * @return AgreementLineRM[]
     */
    protected function fetchLines(array $search): array
    {
        return $this->agreementLineRepo->search(['search' => $search])->getResult();
    }

    /**
     * Dodaje do kryteriów filtr własności klienta, gdy bieżący użytkownik ma ROLE_CUSTOMER.
     * Mierniki o charakterze agregatów firmowych (capacity, bonus) tego nie używają —
     * zgodnie z zasadą "agregaty liczone firmowo".
     *
     * @param array<string, mixed> $search
     * @return array<string, mixed>
     */
    protected function withOwnership(array $search): array
    {
        if ($this->security->isGranted('ROLE_CUSTOMER') && $this->security->getUser() !== null) {
            $search['ownedBy'] = $this->security->getUser();
        }
        return $search;
    }

    /**
     * Lista id przypisanych klientów dla ROLE_CUSTOMER, albo null gdy filtr nie obowiązuje
     * (brak roli / brak przypisanych klientów — zgodnie z dotychczasowym zachowaniem).
     *
     * @return int[]|null
     */
    protected function ownedCustomerIds(): ?array
    {
        $user = $this->security->getUser();
        if (!$this->security->isGranted('ROLE_CUSTOMER') || !$user instanceof User) {
            return null;
        }

        $ids = array_values(array_filter(array_map(
            fn ($customer) => $customer?->getId(),
            $user->getCustomers()->toArray()
        )));

        return empty($ids) ? null : $ids;
    }
}
