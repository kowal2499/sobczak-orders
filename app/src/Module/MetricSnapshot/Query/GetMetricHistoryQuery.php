<?php

namespace App\Module\MetricSnapshot\Query;

use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;

class GetMetricHistoryQuery
{
    /**
     * @param SnapshotMetric[] $metrics
     * @param int[]|null $customerIds null = bez filtra klienta
     * @param string[] $departments działy, których wymiary wolno zwrócić
     * @param string[] $attentionLists listy uwagi, które wolno zwrócić
     */
    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly array $metrics,
        public readonly ?array $customerIds,
        public readonly array $departments,
        public readonly array $attentionLists,
    ) {
    }
}
