<?php

namespace App\Tests\Unit\Modules\MetricSnapshot;

use App\Module\MetricSnapshot\Query\GetMetricHistoryQuery;
use App\Module\MetricSnapshot\QueryHandler\GetMetricHistoryQueryHandler;
use App\Module\MetricSnapshot\Repository\MetricSnapshotRepository;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use PHPUnit\Framework\TestCase;

class GetMetricHistoryQueryHandlerTest extends TestCase
{
    public function testShouldShowAttentionOrderWhenAnyOfItsDepartmentsIsVisible(): void
    {
        // Given
        $handler = new GetMetricHistoryQueryHandler($this->repository([
            'overdueProductions@dpt02,dpt03',
            'overdueProductions@dpt03',
            'overdueProductions@dpt02',
            'notStartedProductions@dpt02',
            'overdueProductions:dpt02',
            'overdueProductions:dpt03',
        ]));

        // When
        $result = $handler(new GetMetricHistoryQuery(
            new \DateTimeImmutable('2026-11-01'),
            new \DateTimeImmutable('2026-11-30'),
            [SnapshotMetric::ATTENTION_LISTS],
            null,
            ['dpt02'],
            ['overdueProductions'],
        ));

        // Then
        $this->assertSame(
            ['overdueProductions@dpt02,dpt03', 'overdueProductions@dpt02', 'overdueProductions:dpt02'],
            array_column($result['series'], 'dimension')
        );
    }

    /**
     * @param string[] $dimensions
     */
    private function repository(array $dimensions): MetricSnapshotRepository
    {
        $repository = $this->createMock(MetricSnapshotRepository::class);
        $repository->method('findLastComputedAt')->willReturn(null);
        $repository->method('findHistory')->willReturn(array_map(fn (string $dimension) => [
            'metric' => SnapshotMetric::ATTENTION_LISTS,
            'dimension' => $dimension,
            'periodKey' => '-',
            'snapshotDate' => new \DateTimeImmutable('2026-11-26'),
            'factorSum' => null,
            'itemCount' => 1,
            'source' => 'cron',
        ], $dimensions));

        return $repository;
    }
}
