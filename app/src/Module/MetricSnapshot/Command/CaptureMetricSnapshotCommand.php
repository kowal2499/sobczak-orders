<?php

namespace App\Module\MetricSnapshot\Command;

use App\Module\MetricSnapshot\ValueObject\SnapshotSource;
use Symfony\Component\Validator\Constraints as Assert;

class CaptureMetricSnapshotCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        public readonly string $date,
        public readonly SnapshotSource $source,
        #[Assert\Positive]
        public readonly ?int $userId = null,
    ) {
    }
}
