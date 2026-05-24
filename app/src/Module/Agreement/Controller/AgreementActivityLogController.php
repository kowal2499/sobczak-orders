<?php

namespace App\Module\Agreement\Controller;

use App\Module\ActivityLog\DTO\FieldFilter;
use App\Module\ActivityLog\DTO\PaginatedLogFilter;
use App\Module\ActivityLog\Query\GetPaginatedLogsQuery;
use App\Module\ActivityLog\ReadModel\LogFieldReadModel;
use App\Module\ActivityLog\ReadModel\LogModel;
use App\Module\ActivityLog\ReadModel\PaginatedLogs;
use App\System\QueryBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AgreementActivityLogController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route(
        '/agreement/{id}/activity-log',
        name: 'agreement_activity_log_list',
        requirements: ['id' => '\d+'],
        options: ['expose' => true],
        methods: ['GET'],
    )]
    #[IsGranted('activity-log.read')]
    public function list(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = min(500, max(1, (int) $request->query->get('pageSize', 50)));

        $filter = new PaginatedLogFilter(
            $page,
            $pageSize,
            [new FieldFilter(name: 'agreementId', value: (string) $id)],
            null,
        );

        /** @var PaginatedLogs $result */
        $result = $this->queryBus->query(new GetPaginatedLogsQuery(null, $filter));

        return $this->json([
            'page' => $result->page,
            'pageSize' => $result->pageSize,
            'total' => $result->total,
            'items' => array_map(fn (LogModel $log) => $this->serialize($log), $result->items),
        ]);
    }

    private function serialize(LogModel $log): array
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
}
