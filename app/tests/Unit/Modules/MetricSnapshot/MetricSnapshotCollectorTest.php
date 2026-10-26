<?php

namespace App\Tests\Unit\Modules\MetricSnapshot;

use App\Module\MetricSnapshot\DTO\MetricValueDTO;
use App\Module\MetricSnapshot\Entity\MetricSnapshot;
use App\Module\MetricSnapshot\Service\MetricSnapshotCollector;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Reports\Production\DTO\AttentionLineDTO;
use App\Module\Reports\Production\DTO\AttentionProductionDTO;
use App\Module\Reports\Production\DTO\CustomerDTO;
use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;
use App\Module\Reports\Production\Metric\AbstractMetricStrategy;
use App\Module\Reports\Production\Metric\MetricStrategyInterface;
use App\Module\Reports\Production\Provider\DashboardMetricProvider;
use App\Module\Reports\Schedule\DTO\ScheduleCapacityDTO;
use App\Module\Reports\Schedule\Service\ScheduleCapacityService;
use PHPUnit\Framework\TestCase;

class MetricSnapshotCollectorTest extends TestCase
{
    /** @var array<string, array<int, array{start: ?\DateTimeInterface, end: ?\DateTimeInterface, options: array}>> */
    private array $calls = [];

    /** @var array<string, array> */
    private array $results = [];

    private ?\DateTimeImmutable $weeklyStart = null;

    private ?\DateTimeImmutable $weeklyEnd = null;

    public function testShouldSumBonusPerDepartmentAndCustomer(): void
    {
        // Given
        $this->results['departments_bonus'] = [
            $this->record('dpt03', 2.0, customerId: 7),
            $this->record('dpt03', 1.5, customerId: 7),
            $this->record('dpt03', 4.0, customerId: 8),
            $this->record('dpt03', 9.0, customerId: 7, isGhost: true),
            $this->record('dpt03', 9.0, customerId: 7, inRange: false),
        ];

        // When
        $values = $this->collect('2026-10-15');

        // Then
        $row = $this->find($values, SnapshotMetric::DEPARTMENTS_BONUS, 'dpt03', customerId: 7);
        $this->assertSame(3.5, $row->factorSum);
        $this->assertSame(2, $row->itemCount);
        $this->assertSame(4.0, $this->find($values, SnapshotMetric::DEPARTMENTS_BONUS, 'dpt03', customerId: 8)->factorSum);
    }

    public function testShouldCountFlowMetricsFromMonthStartAndCompanyWide(): void
    {
        // When
        $this->collect('2026-10-15');

        // Then
        $call = $this->calls['departments_bonus'][0];
        $this->assertSame('2026-10-01', $call['start']->format('Y-m-d'));
        $this->assertSame('2026-10-15', $call['end']->format('Y-m-d'));
        $this->assertTrue($call['options'][AbstractMetricStrategy::OPTION_COMPANY_WIDE]);
        $this->assertTrue($this->calls['departments_bonus_on_time'][0]['options'][AbstractMetricStrategy::OPTION_COMPANY_WIDE]);
        $this->assertTrue($this->calls['attention_lists'][0]['options'][AbstractMetricStrategy::OPTION_COMPANY_WIDE]);
    }

    public function testShouldTakeOrdersFromMetricStrategiesPerCustomer(): void
    {
        // Given
        $this->results['orders_finished'] = [
            ['customer_id' => '7', 'factors_summary' => '4.5', 'count' => '3'],
        ];

        // When
        $values = $this->collect('2026-10-15');

        // Then
        $finished = $this->find($values, SnapshotMetric::ORDERS_FINISHED, 'total', customerId: 7);
        $this->assertSame(4.5, $finished->factorSum);
        $this->assertSame(3, $finished->itemCount);

        $call = $this->calls['orders_finished'][0];
        $this->assertSame('2026-10-01', $call['start']->format('Y-m-d'));
        $this->assertTrue($call['options'][AbstractMetricStrategy::OPTION_BY_CUSTOMER]);
        $this->assertTrue($call['options'][AbstractMetricStrategy::OPTION_COMPANY_WIDE]);
        $this->assertSame('2026-10-15', $this->calls['orders_pending'][0]['end']->format('Y-m-d'));
    }

    public function testShouldSplitOnTimeIntoEarnedAndLost(): void
    {
        // Given
        $this->results['departments_bonus_on_time'] = [
            $this->record('dpt04', 3.0, customerId: 7),
            $this->record('dpt04', 2.0, customerId: 7, onTime: false),
        ];

        // When
        $values = $this->collect('2026-10-15');

        // Then
        $in = $this->find($values, SnapshotMetric::ON_TIME_IN, 'dpt04', customerId: 7);
        $late = $this->find($values, SnapshotMetric::ON_TIME_LATE, 'dpt04', customerId: 7);
        $this->assertSame(3.0, $in->factorSum);
        $this->assertSame(2.0, $late->factorSum);
        $this->assertSame(['toleranceDays' => 5], $in->params);
        $this->assertSame(5, $this->calls['departments_bonus_on_time'][0]['options']['toleranceDays']);
    }

    public function testShouldCountCapacityForWholeCurrentAndNextMonth(): void
    {
        // Given
        $this->results['capacity'] = [$this->record('dpt01', 10.0, customerId: 7)];

        // When
        $values = $this->collect('2026-10-15');

        // Then
        $ranges = array_map(
            fn (array $call) => $call['start']->format('Y-m-d') . '..' . $call['end']->format('Y-m-d'),
            $this->calls['capacity']
        );
        $this->assertSame(['2026-10-01..2026-10-31', '2026-11-01..2026-11-30'], $ranges);

        $october = $this->find($values, SnapshotMetric::CAPACITY, 'dpt01', periodKey: '2026-10');
        $this->assertSame(MetricSnapshot::COMPANY, $october->customerId, 'obłożenie jest agregatem firmowym');
        $this->assertSame(10.0, $october->factorSum);
    }

    public function testShouldTakeWeeklyCapacityFromFirstWorkingDay(): void
    {
        // Given
        $this->results['weekly'] = [
            new ScheduleCapacityDTO(new \DateTimeImmutable('2026-10-13'), 12.0, 5.0),
            new ScheduleCapacityDTO(new \DateTimeImmutable('2026-10-14'), 12.0, 5.0),
            new ScheduleCapacityDTO(new \DateTimeImmutable('2026-10-19'), 10.0, 8.0),
        ];

        // When
        $values = $this->collect('2026-10-15');

        // Then
        $this->assertSame('2026-10-12', $this->weeklyStart->format('Y-m-d'));
        $this->assertSame('2026-11-22', $this->weeklyEnd->format('Y-m-d'));
        $this->assertSame(5.0, $this->find($values, SnapshotMetric::WEEKLY_CAPACITY, 'total', periodKey: '2026-W42')->factorSum);
        $this->assertSame(12.0, $this->find($values, SnapshotMetric::WEEKLY_CAPACITY_LIMIT, 'total', periodKey: '2026-W42')->factorSum);
        $this->assertSame(8.0, $this->find($values, SnapshotMetric::WEEKLY_CAPACITY, 'total', periodKey: '2026-W43')->factorSum);
    }

    public function testShouldCountProductionAttentionListsPerDepartment(): void
    {
        // Given
        $this->results['attention_lists'] = [
            'overdueOrders' => [$this->attentionLine(7), $this->attentionLine(7)],
            'overdueProductions' => [$this->attentionLine(7, ['dpt02', 'dpt03'])],
        ];

        // When
        $values = $this->collect('2026-10-15');

        // Then
        $this->assertSame(2, $this->find($values, SnapshotMetric::ATTENTION_LISTS, 'overdueOrders', customerId: 7)->itemCount);
        $this->assertSame(1, $this->find($values, SnapshotMetric::ATTENTION_LISTS, 'overdueProductions:dpt02', customerId: 7)->itemCount);
        $this->assertSame(1, $this->find($values, SnapshotMetric::ATTENTION_LISTS, 'overdueProductions:dpt03', customerId: 7)->itemCount);
    }

    public function testShouldSkipStateMetricsWithoutPresentState(): void
    {
        // Given
        $this->results['capacity'] = [$this->record('dpt01', 10.0)];
        $this->results['departments_bonus'] = [$this->record('dpt01', 1.0)];

        // When
        $values = $this->collect('2026-09-10', withPresentState: false);

        // Then
        $this->assertArrayNotHasKey('capacity', $this->calls);
        $this->assertArrayNotHasKey('attention_lists', $this->calls);
        $this->assertNull($this->weeklyStart);
        foreach ($values as $value) {
            $this->assertTrue($value->metric->isFlow(), $value->metric->value);
        }
        $this->assertNotEmpty($values);
    }

    /**
     * @return MetricValueDTO[]
     */
    private function collect(string $day, bool $withPresentState = true): array
    {
        $strategies = array_map(
            fn (string $name) => $this->strategy($name),
            [
                'orders_pending',
                'orders_finished',
                'departments_bonus',
                'departments_bonus_on_time',
                'capacity',
                'attention_lists',
            ]
        );

        $schedule = $this->createMock(ScheduleCapacityService::class);
        $schedule->method('calculateBurnout')->willReturnCallback(
            function (\DateTimeImmutable $start, \DateTimeImmutable $end) {
                $this->weeklyStart = $start;
                $this->weeklyEnd = $end;

                return $this->results['weekly'] ?? [];
            }
        );

        $collector = new MetricSnapshotCollector(new DashboardMetricProvider($strategies), $schedule);

        return $collector->collect(new \DateTimeImmutable($day), $withPresentState);
    }

    /**
     * @param MetricValueDTO[] $values
     */
    private function find(
        array $values,
        SnapshotMetric $metric,
        string $dimension,
        int $customerId = MetricSnapshot::COMPANY,
        string $periodKey = MetricSnapshot::NO_PERIOD,
    ): MetricValueDTO {
        foreach ($values as $value) {
            if (
                $value->metric === $metric && $value->dimension === $dimension
                && $value->customerId === $customerId && $value->periodKey === $periodKey
            ) {
                return $value;
            }
        }
        $this->fail(sprintf('brak wartości %s/%s/%s/%d', $metric->value, $dimension, $periodKey, $customerId));
    }

    private function record(
        string $slug,
        float $factor,
        int $customerId = 1,
        bool $onTime = true,
        bool $inRange = true,
        bool $isGhost = false,
    ): ProductionReportRecordDTO {
        return new ProductionReportRecordDTO(
            departmentSlug: $slug,
            customer: new CustomerDTO('Klient', $customerId),
            factors: new AssembledFactorDTO($factor),
            isGhost: $isGhost,
            onTime: $onTime,
            inRange: $inRange,
        );
    }

    /**
     * @param string[] $departments
     */
    private function attentionLine(int $customerId, array $departments = []): AttentionLineDTO
    {
        return new AttentionLineDTO(
            agreementLineId: 1,
            orderNumber: '1/2026',
            customerName: 'Klient',
            productName: null,
            status: null,
            agreementCreateDate: new \DateTimeImmutable(),
            confirmedDate: new \DateTimeImmutable(),
            days: 3,
            productions: array_map(
                fn (string $slug) => new AttentionProductionDTO($slug, null, null, null, 3),
                $departments
            ),
            customerId: $customerId,
        );
    }

    private function strategy(string $name): MetricStrategyInterface
    {
        $test = $this;

        return new class ($name, $test) implements MetricStrategyInterface {
            public function __construct(
                private readonly string $name,
                private readonly MetricSnapshotCollectorTest $test,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function compute(
                ?\DateTimeInterface $start,
                ?\DateTimeInterface $end,
                bool $includeGhost = false,
                array $options = []
            ): array {
                return $this->test->recordCall($this->name, $start, $end, $options);
            }
        };
    }

    public function recordCall(string $name, ?\DateTimeInterface $start, ?\DateTimeInterface $end, array $options): array
    {
        $this->calls[$name][] = ['start' => $start, 'end' => $end, 'options' => $options];

        return $this->results[$name] ?? [];
    }
}
