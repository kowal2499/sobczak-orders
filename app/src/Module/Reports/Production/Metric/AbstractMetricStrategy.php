<?php

namespace App\Module\Reports\Production\Metric;

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
}
