<?php

namespace App\Module\MetricSnapshot\QueryHandler;

use App\Module\MetricSnapshot\Query\GetMetricHistoryQuery;
use App\Module\MetricSnapshot\Repository\MetricSnapshotRepository;
use App\Module\MetricSnapshot\ValueObject\SnapshotMetric;
use App\Module\Production\ValueObject\DepartmentEnum;

class GetMetricHistoryQueryHandler
{
    public function __construct(
        private readonly MetricSnapshotRepository $repository,
    ) {
    }

    /**
     * @return array{computedAt: ?string, series: array<int, array<string, mixed>>}
     */
    public function __invoke(GetMetricHistoryQuery $query): array
    {
        $rows = $this->repository->findHistory($query->from, $query->to, $query->metrics, $query->customerIds);

        $series = [];
        foreach ($rows as $row) {
            $metric = $row['metric'] instanceof SnapshotMetric ? $row['metric'] : SnapshotMetric::from($row['metric']);
            if (!$this->isVisible($metric, $row['dimension'], $query)) {
                continue;
            }

            $key = implode('|', [$metric->value, $row['dimension'], $row['periodKey']]);
            $series[$key] ??= [
                'metric' => $metric->value,
                'dimension' => $row['dimension'],
                'periodKey' => $row['periodKey'],
                'flow' => $metric->isFlow(),
                'points' => [],
            ];
            $series[$key]['points'][] = [
                'date' => $row['snapshotDate']->format('Y-m-d'),
                'factorSum' => null === $row['factorSum'] ? null : round((float) $row['factorSum'], 4),
                'itemCount' => (int) $row['itemCount'],
                'source' => $row['source'],
            ];
        }

        return [
            'computedAt' => $this->repository->findLastComputedAt()?->format(\DateTimeInterface::ATOM),
            'series' => array_values($series),
        ];
    }

    /**
     * Wymiar to slug działu, "lista", "lista:dział" (listy uwagi per produkcja) albo
     * "lista@dział,dział" (listy uwagi per zamówienie - widoczne, gdy widać choć jeden dział).
     */
    private function isVisible(SnapshotMetric $metric, string $dimension, GetMetricHistoryQuery $query): bool
    {
        $parts = explode(':', $dimension, 2);

        if (SnapshotMetric::ATTENTION_LISTS === $metric) {
            if (str_contains($dimension, '@')) {
                [$list, $departments] = explode('@', $dimension, 2);

                return in_array($list, $query->attentionLists, true)
                    && [] !== array_intersect(explode(',', $departments), $query->departments);
            }
            if (!in_array($parts[0], $query->attentionLists, true)) {
                return false;
            }

            return !isset($parts[1]) || in_array($parts[1], $query->departments, true);
        }

        return null === DepartmentEnum::tryFrom($dimension) || in_array($dimension, $query->departments, true);
    }
}
