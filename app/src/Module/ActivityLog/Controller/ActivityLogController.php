<?php

namespace App\Module\ActivityLog\Controller;

use App\Controller\BaseController;
use App\Module\ActivityLog\DTO\FieldFilter;
use App\Module\ActivityLog\DTO\FieldsFilter;
use App\Module\ActivityLog\DTO\PaginatedLogFilter;
use App\Module\ActivityLog\Query\CountLogsByFieldQuery;
use App\Module\ActivityLog\Query\GetPaginatedLogsQuery;
use App\Module\ActivityLog\ReadModel\LogCountByField;
use App\Module\ActivityLog\ReadModel\LogFieldReadModel;
use App\Module\ActivityLog\ReadModel\LogModel;
use App\Module\ActivityLog\ReadModel\PaginatedLogs;
use App\System\QueryBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/log')]
class ActivityLogController extends BaseController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route(['', '/{type}'], methods: ['GET'], requirements: ['type' => '[a-zA-Z0-9_.\-]+'], defaults: ['type' => null])]
    #[IsGranted('activity-log.read')]
    public function list(?string $type, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        try {
            $filter = $this->buildPaginatedLogFilter($data, $request);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($filter);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var PaginatedLogs $result */
        $result = $this->queryBus->query(new GetPaginatedLogsQuery($type, $filter));

        return $this->json([
            'page' => $result->page,
            'pageSize' => $result->pageSize,
            'total' => $result->total,
            'items' => array_map(fn (LogModel $log) => $this->serializeLog($log), $result->items),
        ]);
    }

    #[Route('/{type}/count-by/{groupBy}', methods: ['GET'], requirements: [
        'type' => '[a-zA-Z0-9_.\-]+',
        'groupBy' => '[a-zA-Z0-9_.\-]+',
    ])]
    #[IsGranted('activity-log.read')]
    public function countBy(string $type, string $groupBy, Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);

        try {
            $filters = $this->buildFieldsFilter($data);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $violations = $this->validator->validate($filters);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var LogCountByField[] $rows */
        $rows = $this->queryBus->query(new CountLogsByFieldQuery($type, $groupBy, $filters));

        return $this->json(array_map(
            static fn (LogCountByField $row) => ['value' => $row->value, 'count' => $row->count],
            $rows,
        ));
    }

    private function decodeJson(Request $request): array
    {
        $raw = $request->getContent();
        if ($raw === '' || $raw === null) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Request body must be a JSON object.');
        }

        return $decoded;
    }

    private function buildPaginatedLogFilter(array $data, Request $request): PaginatedLogFilter
    {
        $page = (int) ($data['page'] ?? $request->query->get('page') ?? 1);
        $pageSize = (int) ($data['pageSize'] ?? $request->query->get('pageSize') ?? 50);
        $filterBy = $data['filterBy'] ?? $request->query->get('filterBy');
        $filterBy = $filterBy === null ? null : (string) $filterBy;

        $fields = $this->parseFieldFilters($data['fields'] ?? []);

        return new PaginatedLogFilter($page, $pageSize, $fields, $filterBy);
    }

    private function buildFieldsFilter(array $data): FieldsFilter
    {
        return new FieldsFilter($this->parseFieldFilters($data['fields'] ?? []));
    }

    /**
     * @return FieldFilter[]
     */
    private function parseFieldFilters(mixed $raw): array
    {
        if (!is_array($raw)) {
            throw new \InvalidArgumentException('"fields" must be an array.');
        }

        $result = [];
        foreach ($raw as $item) {
            if (!is_array($item) || !isset($item['name'])) {
                throw new \InvalidArgumentException('Each field filter must have at least "name".');
            }

            $values = $item['values'] ?? null;
            if ($values !== null && !is_array($values)) {
                throw new \InvalidArgumentException('"values" must be an array if provided.');
            }

            $result[] = new FieldFilter(
                name: (string) $item['name'],
                value: isset($item['value']) ? (string) $item['value'] : null,
                values: $values === null ? null : array_map('strval', $values),
            );
        }
        return $result;
    }

    private function serializeLog(LogModel $log): array
    {
        return [
            'id' => $log->id,
            'type' => $log->type,
            'content' => $log->content,
            'contentParams' => $log->contentParams,
            'date' => $log->date->format(\DateTimeInterface::ATOM),
            'level' => $log->level->value,
            'priority' => $log->priority->value,
            'user' => $log->user === null ? null : [
                'id' => $log->user->id,
                'name' => $log->user->name,
            ],
            'fields' => array_map(
                static fn (LogFieldReadModel $f) => ['name' => $f->name, 'value' => $f->value],
                $log->fields,
            ),
        ];
    }

    private function formatViolations(ConstraintViolationListInterface $violations): array
    {
        $out = [];
        foreach ($violations as $v) {
            $out[] = [
                'path' => $v->getPropertyPath(),
                'message' => $v->getMessage(),
            ];
        }
        return $out;
    }
}
