<?php

namespace App\Module\MetricSnapshot\Service;

use App\Module\MetricSnapshot\DTO\MetricValueDTO;
use App\Module\MetricSnapshot\Entity\MetricSnapshot;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use App\Module\Reports\Production\DTO\AttentionLineDTO;
use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;
use App\Module\Reports\Production\Metric\AbstractMetricStrategy;
use App\Module\Reports\Production\Metric\DepartmentFactorRule;
use App\Module\Reports\Production\Metric\DepartmentsBonusOnTimeMetricStrategy;
use App\Module\Reports\Production\Provider\DashboardMetricProvider;
use App\Module\Reports\Schedule\Service\ScheduleCapacityService;

/**
 * Liczy wartości wszystkich mierników pulpitu dla dnia - zawsze firmowo, w rozbiciu na klientów,
 * żeby widok dowolnego użytkownika dało się złożyć z sum wierszy jego klientów.
 *
 * Wartości pochodzą z tych samych strategii co kafle (DashboardMetricProvider) i z tego samego
 * serwisu co obłożenie tygodniowe - kolektor niczego nie liczy sam, tylko dzieli wynik na wymiary.
 */
class MetricSnapshotCollector
{
    public const WEEKS_AHEAD = 6;

    private const PRODUCTION_LISTS = ['notStartedProductions', 'overdueProductions'];

    private const BY_CUSTOMER = [
        AbstractMetricStrategy::OPTION_COMPANY_WIDE => true,
        AbstractMetricStrategy::OPTION_BY_CUSTOMER => true,
    ];

    /** @var array<string, MetricValueDTO> */
    private array $values = [];

    public function __construct(
        private readonly DashboardMetricProvider $metrics,
        private readonly ScheduleCapacityService $scheduleCapacity,
    ) {
    }

    /**
     * @param bool $withPresentState false pomija mierniki stanowe - przy uzupełnianiu wstecz
     * @return MetricValueDTO[]
     */
    public function collect(\DateTimeImmutable $day, bool $withPresentState = true): array
    {
        $this->values = [];
        $day = $day->setTime(0, 0);
        $monthStart = $day->modify('first day of this month');

        $this->collectFinishedOrders($monthStart, $day);
        $this->collectDepartmentsBonus($monthStart, $day);
        $this->collectOnTime($monthStart, $day);

        if ($withPresentState) {
            $this->collectPendingOrders($day);
            $this->collectCapacity($monthStart);
            $this->collectWeeklyCapacity($day);
            $this->collectAttentionLists();
        }

        return array_values($this->values);
    }

    private function collectPendingOrders(\DateTimeImmutable $day): void
    {
        $rows = $this->metrics->getMetric('orders_pending', null, $day, false, self::BY_CUSTOMER);
        foreach ($rows as $row) {
            $this->addSummaryRow(SnapshotMetric::ORDERS_PENDING, $row);
        }
    }

    private function collectFinishedOrders(\DateTimeImmutable $monthStart, \DateTimeImmutable $day): void
    {
        $rows = $this->metrics->getMetric('orders_finished', $monthStart, $day, false, self::BY_CUSTOMER);
        foreach ($rows as $row) {
            $this->addSummaryRow(SnapshotMetric::ORDERS_FINISHED, $row);
        }
    }

    /**
     * @param array{customer_id: int|string, factors_summary: string|float|null, count: int|string} $row
     */
    private function addSummaryRow(SnapshotMetric $metric, array $row): void
    {
        $this->add(
            $metric,
            MetricSnapshot::TOTAL,
            MetricSnapshot::NO_PERIOD,
            (int) $row['customer_id'],
            (float) $row['factors_summary'],
            (int) $row['count'],
        );
    }

    private function collectDepartmentsBonus(\DateTimeImmutable $monthStart, \DateTimeImmutable $day): void
    {
        $records = $this->metrics->getMetric('departments_bonus', $monthStart, $day, false, [
            AbstractMetricStrategy::OPTION_COMPANY_WIDE => true,
        ]);

        foreach ($records as $record) {
            if (DepartmentFactorRule::counts($record)) {
                $this->addRecord(SnapshotMetric::DEPARTMENTS_BONUS, $record);
            }
        }
    }

    /**
     * Zawsze z widełkami obowiązującymi dla rozliczeń - suwak na pulpicie to tylko podgląd.
     */
    private function collectOnTime(\DateTimeImmutable $monthStart, \DateTimeImmutable $day): void
    {
        $tolerance = DepartmentsBonusOnTimeMetricStrategy::DEFAULT_TOLERANCE_DAYS;
        $params = ['toleranceDays' => $tolerance];

        $records = $this->metrics->getMetric('departments_bonus_on_time', $monthStart, $day, false, [
            'toleranceDays' => $tolerance,
            AbstractMetricStrategy::OPTION_COMPANY_WIDE => true,
        ]);

        foreach ($records as $record) {
            if (DepartmentFactorRule::counts($record)) {
                $this->addRecord(SnapshotMetric::ON_TIME_IN, $record, $params);
            } elseif (DepartmentFactorRule::lostToDelay($record)) {
                $this->addRecord(SnapshotMetric::ON_TIME_LATE, $record, $params);
            }
        }
    }

    /**
     * Obłożenie to plan, więc liczymy pełny bieżący i następny miesiąc, a nie zakres do dziś.
     */
    private function collectCapacity(\DateTimeImmutable $monthStart): void
    {
        foreach ([$monthStart, $monthStart->modify('+1 month')] as $periodStart) {
            $periodEnd = $periodStart->modify('last day of this month');
            $records = $this->metrics->getMetric('capacity', $periodStart, $periodEnd);

            foreach ($records as $record) {
                if (!DepartmentFactorRule::counts($record)) {
                    continue;
                }
                $this->add(
                    SnapshotMetric::CAPACITY,
                    $record->getDepartmentSlug(),
                    $periodStart->format('Y-m'),
                    MetricSnapshot::COMPANY,
                    $record->getFactors()->factor,
                    1,
                );
            }
        }
    }

    /**
     * Serwis zwraca wiersz per dzień roboczy z wartościami całego tygodnia - bierzemy pierwszy
     * dzień tygodnia, tak jak kafel. Tydzień bez dni roboczych nie ma wiersza i jest pomijany.
     */
    private function collectWeeklyCapacity(\DateTimeImmutable $day): void
    {
        $start = $day->modify('monday this week');
        $end = $start->modify(sprintf('+%d weeks', self::WEEKS_AHEAD))->modify('-1 day');

        $seen = [];
        foreach ($this->scheduleCapacity->calculateBurnout($start, $end) as $dayCapacity) {
            $week = $dayCapacity->date->format('o-\WW');
            if (isset($seen[$week])) {
                continue;
            }
            $seen[$week] = true;

            $this->add(
                SnapshotMetric::WEEKLY_CAPACITY,
                MetricSnapshot::TOTAL,
                $week,
                MetricSnapshot::COMPANY,
                (float) $dayCapacity->capacityBurned,
                // lista linii jest już zawężona do klientów wywołującego, suma nie
                0,
            );
            $this->add(
                SnapshotMetric::WEEKLY_CAPACITY_LIMIT,
                MetricSnapshot::TOTAL,
                $week,
                MetricSnapshot::COMPANY,
                (float) $dayCapacity->capacity,
                0,
            );
        }
    }

    /**
     * Listy produkcyjne liczą produkcje per dział (wymiar "lista:dział"), żeby odczyt mógł
     * odfiltrować działy bez grantu; listy zamówień liczą linie.
     */
    private function collectAttentionLists(): void
    {
        $lists = $this->metrics->getMetric('attention_lists', null, null, false, [
            AbstractMetricStrategy::OPTION_COMPANY_WIDE => true,
        ]);

        foreach ($lists as $list => $lines) {
            /** @var AttentionLineDTO $line */
            foreach ($lines as $line) {
                $customerId = (int) $line->customerId;

                if (!in_array($list, self::PRODUCTION_LISTS, true)) {
                    $this->add(SnapshotMetric::ATTENTION_LISTS, $list, MetricSnapshot::NO_PERIOD, $customerId, null, 1);
                    continue;
                }
                foreach ($line->productions as $production) {
                    $this->add(
                        SnapshotMetric::ATTENTION_LISTS,
                        $list . ':' . $production->departmentSlug,
                        MetricSnapshot::NO_PERIOD,
                        $customerId,
                        null,
                        1,
                    );
                }
            }
        }
    }

    /**
     * @param array<string, mixed>|null $params
     */
    private function addRecord(SnapshotMetric $metric, ProductionReportRecordDTO $record, ?array $params = null): void
    {
        $this->add(
            $metric,
            $record->getDepartmentSlug(),
            MetricSnapshot::NO_PERIOD,
            (int) $record->getCustomer()?->getId(),
            $record->getFactors()->factor,
            1,
            $params,
        );
    }

    /**
     * @param array<string, mixed>|null $params
     */
    private function add(
        SnapshotMetric $metric,
        string $dimension,
        string $periodKey,
        int $customerId,
        ?float $factor,
        int $count,
        ?array $params = null,
    ): void {
        $key = implode('|', [$metric->value, $dimension, $periodKey, $customerId]);
        $current = $this->values[$key] ?? null;

        $this->values[$key] = new MetricValueDTO(
            $metric,
            $dimension,
            $periodKey,
            $customerId,
            null === $factor && null === $current?->factorSum ? null : ($current?->factorSum ?? 0.0) + ($factor ?? 0.0),
            ($current?->itemCount ?? 0) + $count,
            $params,
        );
    }
}
