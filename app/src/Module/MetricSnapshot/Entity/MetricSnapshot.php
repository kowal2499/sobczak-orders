<?php

namespace App\Module\MetricSnapshot\Entity;

use App\Module\MetricSnapshot\Repository\MetricSnapshotRepository;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use App\Module\MetricSnapshot\ValueObject\SnapshotSource;
use Doctrine\ORM\Mapping as ORM;

/**
 * Wartość miernika pulpitu zamrożona dla dnia. Przepływowe są narastające od 1. dnia miesiąca,
 * stanowe opisują stan na dzień (patrz SnapshotMetric).
 *
 * customerId = 0 oznacza wiersz firmowy, a periodKey = '-' brak okresu - MySQL traktuje NULL-e
 * w indeksie unikalnym jako różne, więc puste wartości złamałyby unikalność dnia.
 */
#[ORM\Entity(repositoryClass: MetricSnapshotRepository::class)]
#[ORM\Table(name: 'metric_snapshot')]
#[ORM\UniqueConstraint(
    name: 'unique_metric_snapshot',
    columns: ['snapshot_date', 'metric', 'dimension', 'period_key', 'customer_id']
)]
class MetricSnapshot
{
    public const COMPANY = 0;
    public const NO_PERIOD = '-';
    public const TOTAL = 'total';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $snapshotDate;

    #[ORM\Column(type: 'string', length: 32, enumType: SnapshotMetric::class)]
    private SnapshotMetric $metric;

    #[ORM\Column(type: 'string', length: 64)]
    private string $dimension;

    #[ORM\Column(type: 'string', length: 16)]
    private string $periodKey;

    #[ORM\Column(type: 'integer')]
    private int $customerId;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $factorSum;

    #[ORM\Column(type: 'integer')]
    private int $itemCount;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $params;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $computedAt;

    #[ORM\Column(type: 'string', length: 16, enumType: SnapshotSource::class)]
    private SnapshotSource $source;

    /**
     * @param array<string, mixed>|null $params
     */
    public function __construct(
        \DateTimeImmutable $snapshotDate,
        SnapshotMetric $metric,
        string $dimension,
        string $periodKey,
        int $customerId,
        ?float $factorSum,
        int $itemCount,
        ?array $params,
        \DateTimeImmutable $computedAt,
        SnapshotSource $source,
    ) {
        $this->snapshotDate = $snapshotDate;
        $this->metric = $metric;
        $this->dimension = $dimension;
        $this->periodKey = $periodKey;
        $this->customerId = $customerId;
        $this->factorSum = $factorSum;
        $this->itemCount = $itemCount;
        $this->params = $params;
        $this->computedAt = $computedAt;
        $this->source = $source;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSnapshotDate(): \DateTimeImmutable
    {
        return $this->snapshotDate;
    }

    public function getMetric(): SnapshotMetric
    {
        return $this->metric;
    }

    public function getDimension(): string
    {
        return $this->dimension;
    }

    public function getPeriodKey(): string
    {
        return $this->periodKey;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getFactorSum(): ?float
    {
        return $this->factorSum;
    }

    public function getItemCount(): int
    {
        return $this->itemCount;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    public function getComputedAt(): \DateTimeImmutable
    {
        return $this->computedAt;
    }

    public function getSource(): SnapshotSource
    {
        return $this->source;
    }
}
