<?php

namespace App\Module\ActivityLog\QueryHandler;

use App\Module\ActivityLog\Query\CountLogsByFieldQuery;
use App\Module\ActivityLog\Query\Helper\LogFinder;
use App\Module\ActivityLog\ReadModel\LogCountByField;
use App\Module\ActivityLog\Repository\ActivityLogRepository;

class CountLogsByFieldQueryHandler
{
    public function __construct(
        private readonly ActivityLogRepository $activityLogRepository,
    ) {
    }

    /**
     * @return LogCountByField[]
     */
    public function __invoke(CountLogsByFieldQuery $query): array
    {
        $qb = $this->activityLogRepository->createQueryBuilder('lg')
            ->select('lfs.value AS value, COUNT(DISTINCT lg.id) AS cnt')
            ->innerJoin('lg.logFields', 'lfs', 'WITH', 'lfs.name = :groupBy')
            ->setParameter('groupBy', $query->groupBy)
            ->groupBy('lfs.value');

        LogFinder::applyTypeFilter($qb, 'lg', $query->type);

        foreach ($query->filters->fields as $index => $fieldFilter) {
            LogFinder::applyFieldFilter($qb, 'lg', $fieldFilter, $index);
        }

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row) => new LogCountByField((string) $row['value'], (int) $row['cnt']),
            $rows,
        );
    }
}
