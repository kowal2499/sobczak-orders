<?php

namespace App\Module\Agreement\Controller;

use App\Module\ActivityLog\DTO\FieldFilter;
use App\Module\ActivityLog\DTO\PaginatedLogFilter;
use App\Module\ActivityLog\Query\GetPaginatedLogsQuery;
use App\Module\ActivityLog\Query\Helper\LogResponseMapper;
use App\Module\ActivityLog\ReadModel\PaginatedLogs;
use App\Repository\AgreementRepository;
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
        private readonly LogResponseMapper $logMapper,
        private readonly AgreementRepository $agreementRepository,
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
        $agreement = $this->agreementRepository->find($id);
        if ($agreement !== null) {
            $this->denyAccessUnlessGranted('ASSIGNED_CUSTOMER', $agreement);
        } elseif ($this->isGranted('ROLE_CUSTOMER')) {
            // logi usuniętego zamówienia - nie da się ustalić klienta
            throw $this->createAccessDeniedException();
        }

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

        return $this->json(
            $this->logMapper->toPage($result->items, $result->total, $result->page, $result->pageSize)
        );
    }
}
