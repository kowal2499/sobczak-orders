<?php

namespace App\Module\ActivityLog\Query\Helper;

use App\Module\ActivityLog\DTO\FieldFilter;
use Doctrine\ORM\QueryBuilder;

final class LogFinder
{
    public static function applyTypeFilter(QueryBuilder $qb, string $rootAlias, ?string $type): void
    {
        if ($type === null) {
            return;
        }

        $qb->andWhere(sprintf('%s.type = :logType', $rootAlias))
            ->setParameter('logType', $type);
    }

    public static function applyFieldFilter(
        QueryBuilder $qb,
        string $rootAlias,
        FieldFilter $filter,
        int $aliasIndex,
    ): void {
        $alias = sprintf('lf_%d', $aliasIndex);
        $nameParam = sprintf('lfName_%d', $aliasIndex);

        $on = sprintf('%s.name = :%s', $alias, $nameParam);

        if ($filter->values !== null && $filter->values !== []) {
            $valuesParam = sprintf('lfValues_%d', $aliasIndex);
            $on .= sprintf(' AND %s.value IN (:%s)', $alias, $valuesParam);
            $qb->setParameter($valuesParam, $filter->values);
        } else {
            $valueParam = sprintf('lfValue_%d', $aliasIndex);
            $on .= sprintf(' AND %s.value = :%s', $alias, $valueParam);
            $qb->setParameter($valueParam, $filter->value);
        }

        $qb->innerJoin(sprintf('%s.logFields', $rootAlias), $alias, 'WITH', $on)
            ->setParameter($nameParam, $filter->name);
    }

    public static function applyFilterByPresence(
        QueryBuilder $qb,
        string $rootAlias,
        string $fieldName,
        int $aliasIndex,
    ): void {
        $alias = sprintf('lfb_%d', $aliasIndex);
        $nameParam = sprintf('lfbName_%d', $aliasIndex);

        $qb->innerJoin(
            sprintf('%s.logFields', $rootAlias),
            $alias,
            'WITH',
            sprintf('%s.name = :%s', $alias, $nameParam),
        )->setParameter($nameParam, $fieldName);
    }
}
