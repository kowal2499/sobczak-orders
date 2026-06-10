<?php

namespace App\Module\Reports\Production\Provider;

use App\Module\Reports\Production\Metric\MetricStrategyInterface;

/**
 * Jedna klasa dostarczająca wartości mierników dashboardu. Wybiera strategię po nazwie
 * i deleguje wyliczenie. Wszystkie strategie czerpią dane z read modelu AgreementLineRM.
 */
class DashboardMetricProvider
{
    /** @var array<string, MetricStrategyInterface> */
    private array $strategies = [];

    /**
     * @param iterable<MetricStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->getName()] = $strategy;
        }
    }

    public function has(string $metric): bool
    {
        return isset($this->strategies[$metric]);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getMetric(
        string $metric,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        bool $includeGhost = false
    ): array {
        if (!isset($this->strategies[$metric])) {
            throw new \InvalidArgumentException(sprintf('Unknown dashboard metric "%s".', $metric));
        }

        return $this->strategies[$metric]->compute($start, $end, $includeGhost);
    }
}
