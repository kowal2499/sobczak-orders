<?php

namespace App\Module\MetricSnapshot\Service;

use App\Entity\User;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use App\Module\Production\ValueObject\DepartmentGrantMap;
use App\Module\Reports\Production\Metric\AttentionListsMetricStrategy;
use Symfony\Component\Security\Core\Security;

/**
 * Co z historii mierników widzi bieżący użytkownik - te same granty co kafle pulpitu,
 * ten sam filtr klienta co mierniki liczone na żywo.
 */
class MetricVisibility
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * @return SnapshotMetric[]
     */
    public function metrics(): array
    {
        return array_values(array_filter(SnapshotMetric::cases(), fn (SnapshotMetric $metric) => match ($metric) {
            SnapshotMetric::ORDERS_PENDING,
            SnapshotMetric::ORDERS_FINISHED,
            SnapshotMetric::DEPARTMENTS_BONUS,
            SnapshotMetric::ATTENTION_LISTS => $this->security->isGranted('ROLE_PRODUCTION'),
            SnapshotMetric::ON_TIME_IN,
            SnapshotMetric::ON_TIME_LATE => $this->security->isGranted('reports.dashboard:on-time-bonus'),
            SnapshotMetric::CAPACITY => $this->security->isGranted('reports.dashboard:capacity-utilization')
                || $this->security->isGranted('ROLE_ADMIN'),
            SnapshotMetric::WEEKLY_CAPACITY,
            SnapshotMetric::WEEKLY_CAPACITY_LIMIT => $this->security->isGranted('reports.dashboard:weekly-capacity'),
        }));
    }

    /**
     * @return string[] slugi działów z grantem production.show.*
     */
    public function departments(): array
    {
        return array_keys(array_filter(
            DepartmentGrantMap::all(),
            fn (string $grant) => $this->security->isGranted($grant)
        ));
    }

    /**
     * @return string[]
     */
    public function attentionLists(): array
    {
        return array_keys(array_filter(
            AttentionListsMetricStrategy::LIST_GRANTS,
            fn (string $grant) => $this->security->isGranted($grant)
        ));
    }

    /**
     * Jak AbstractMetricStrategy::ownedCustomerIds(): brak roli albo brak przypisanych
     * klientów oznacza brak filtra.
     *
     * @return int[]|null
     */
    public function customerIds(): ?array
    {
        $user = $this->security->getUser();
        if (!$this->security->isGranted('ROLE_CUSTOMER') || !$user instanceof User) {
            return null;
        }

        $ids = array_values(array_filter(array_map(
            fn ($customer) => $customer?->getId(),
            $user->getCustomers()->toArray()
        )));

        return $ids ?: null;
    }
}
