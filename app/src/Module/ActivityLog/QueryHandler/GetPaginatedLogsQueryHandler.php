<?php

namespace App\Module\ActivityLog\QueryHandler;

use App\Module\ActivityLog\DTO\PaginatedLogFilter;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Entity\LogField;
use App\Module\ActivityLog\Query\GetPaginatedLogsQuery;
use App\Module\ActivityLog\Query\Helper\LogFinder;
use App\Module\ActivityLog\ReadModel\LogFieldReadModel;
use App\Module\ActivityLog\ReadModel\LogModel;
use App\Module\ActivityLog\ReadModel\LogUserReadModel;
use App\Module\ActivityLog\ReadModel\PaginatedLogs;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Contracts\Translation\TranslatorInterface;

class GetPaginatedLogsQueryHandler
{
    private const TRANSLATION_DOMAIN = 'activity_log';

    public function __construct(
        private readonly ActivityLogRepository $activityLogRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(GetPaginatedLogsQuery $query): PaginatedLogs
    {
        $filter = $query->filter;

        $qb = $this->activityLogRepository->createQueryBuilder('lg')
            ->leftJoin('lg.user', 'u')
            ->addSelect('u')
            ->orderBy('lg.createdAt', 'DESC');

        LogFinder::applyTypeFilter($qb, 'lg', $query->type);

        foreach ($filter->fields as $index => $fieldFilter) {
            LogFinder::applyFieldFilter($qb, 'lg', $fieldFilter, $index);
        }

        if ($filter->filterBy !== null) {
            LogFinder::applyFilterByPresence($qb, 'lg', $filter->filterBy, count($filter->fields));
        }

        $qb->setFirstResult(($filter->page - 1) * $filter->pageSize)
            ->setMaxResults($filter->pageSize);

        $paginator = new Paginator($qb->getQuery(), fetchJoinCollection: true);
        $total = count($paginator);

        $allowedFieldNames = $this->resolveAllowedFieldNames($filter);

        $items = [];
        foreach ($paginator as $log) {
            /** @var ActivityLog $log */
            $items[] = $this->toReadModel($log, $allowedFieldNames);
        }

        return new PaginatedLogs($items, $total, $filter->page, $filter->pageSize);
    }

    /**
     * @return array<string, true>|null null = include all fields
     */
    private function resolveAllowedFieldNames(PaginatedLogFilter $filter): ?array
    {
        if ($filter->filterBy === null) {
            return null;
        }

        $allowed = [$filter->filterBy => true];
        foreach ($filter->fields as $f) {
            $allowed[$f->name] = true;
        }
        return $allowed;
    }

    /**
     * @param array<string, true>|null $allowedFieldNames
     */
    private function toReadModel(ActivityLog $log, ?array $allowedFieldNames): LogModel
    {
        $user = $log->getUser();
        $userModel = $user === null
            ? null
            : new LogUserReadModel((int) $user->getId(), $user->getUserFullName());

        $fields = [];
        foreach ($log->getLogFields() as $field) {
            /** @var LogField $field */
            if ($allowedFieldNames !== null && !isset($allowedFieldNames[$field->getName()])) {
                continue;
            }
            $fields[] = new LogFieldReadModel($field->getName(), $field->getValue());
        }

        return new LogModel(
            id: (int) $log->getId(),
            type: $log->getType(),
            content: $this->translateContent($log->getContent(), $log->getContentParams()),
            date: $log->getCreatedAt(),
            user: $userModel,
            level: $log->getLevel(),
            priority: $log->getPriority(),
            fields: $fields,
            contentParams: $log->getContentParams(),
        );
    }

    private function translateContent(string $key, ?array $params): string
    {
        $translatorParams = [];
        foreach ($params ?? [] as $name => $value) {
            $translatorParams['%' . $name . '%'] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $this->translator->trans($key, $translatorParams, self::TRANSLATION_DOMAIN);
    }
}
