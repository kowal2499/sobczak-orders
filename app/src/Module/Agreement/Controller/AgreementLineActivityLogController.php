<?php

namespace App\Module\Agreement\Controller;

use App\Module\ActivityLog\DTO\FieldFilter;
use App\Module\ActivityLog\DTO\PaginatedLogFilter;
use App\Module\ActivityLog\Query\GetPaginatedLogsQuery;
use App\Module\ActivityLog\ReadModel\LogFieldReadModel;
use App\Module\ActivityLog\ReadModel\LogModel;
use App\Module\ActivityLog\ReadModel\PaginatedLogs;
use App\Repository\AgreementLineRepository;
use App\System\QueryBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AgreementLineActivityLogController extends AbstractController
{
    private const MAX_PER_QUERY = 500;

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly AgreementLineRepository $agreementLineRepository,
    ) {
    }

    #[Route(
        '/agreement-line/{id}/activity-log',
        name: 'agreement_line_activity_log_list',
        requirements: ['id' => '\d+'],
        options: ['expose' => true],
        methods: ['GET'],
    )]
    #[IsGranted('activity-log.read')]
    public function list(int $id, Request $request): JsonResponse
    {
        $line = $this->agreementLineRepository->find($id);
        if ($line === null) {
            return $this->json(['error' => 'AgreementLine not found'], Response::HTTP_NOT_FOUND);
        }
        $agreementId = $line->getAgreement()->getId();

        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = min(self::MAX_PER_QUERY, max(1, (int) $request->query->get('pageSize', 50)));

        // Agreement-level logs (e.g. agreement.created) that share this agreementId.
        $agreementLogs = $this->fetchAll('agreement.', 'agreementId', (string) $agreementId);

        // Line-level logs (agreement_line.*) for this specific line.
        $lineLogs = $this->fetchAll('agreement_line.', 'id', (string) $id);

        /** @var LogModel[] $merged */
        $merged = array_merge($agreementLogs, $lineLogs);
        usort($merged, static fn (LogModel $a, LogModel $b) => $b->date <=> $a->date);

        $total = count($merged);
        $paged = array_slice($merged, ($page - 1) * $pageSize, $pageSize);

        return $this->json([
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'items' => array_map(fn (LogModel $log) => $this->serialize($log), $paged),
        ]);
    }

    /**
     * @return LogModel[]
     */
    private function fetchAll(string $typePrefix, string $fieldName, string $fieldValue): array
    {
        /** @var PaginatedLogs $result */
        $result = $this->queryBus->query(new GetPaginatedLogsQuery(
            null,
            new PaginatedLogFilter(
                page: 1,
                pageSize: self::MAX_PER_QUERY,
                fields: [new FieldFilter(name: $fieldName, value: $fieldValue)],
                filterBy: null,
                typePrefix: $typePrefix,
            ),
        ));
        return $result->items;
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
