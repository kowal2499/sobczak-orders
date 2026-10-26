<?php

namespace App\Module\MetricSnapshot\DTO;

use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;

class MetricValueDTO
{
    /**
     * @param array<string, mixed>|null $params
     */
    public function __construct(
        public readonly SnapshotMetric $metric,
        public readonly string $dimension,
        public readonly string $periodKey,
        public readonly int $customerId,
        public readonly ?float $factorSum,
        public readonly int $itemCount,
        public readonly ?array $params = null,
    ) {
    }
}
