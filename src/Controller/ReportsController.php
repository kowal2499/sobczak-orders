<?php

namespace App\Controller;

use App\Modules\Reports\Production\ProductionReport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/reports')]
class ReportsController extends BaseController
{
    /**
     * @deprecated
     */
    #[Route(path: '/agreement-line-production', methods: ['GET'])]
    public function agreementLinesProduction(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');
        $departments = $request->query->get('departments', []);

        return $this->json($report->calc(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null,
            $departments
        ));
    }

    #[Route(path: '/agreement-line-production-summary', methods: ['GET'])]
    public function agreementLinesProductionSummary(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->json($report->getSummary(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null
        ));
    }

    #[Route(path: '/production-finished-details', methods: ['GET'])]
    public function productionFinishedDetails(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->json($report->getOrdersFinishedDetails(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null
        ));
    }

    #[Route(path: '/production-pending-details', methods: ['GET'])]
    public function productionPendingDetails(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->json($report->getOrdersPendingDetails(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null
        ));
    }

    #[Route(path: '/production-tasks-completion-summary', methods: ['GET'])]
    public function productionTasksCompletionSummary(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start', null);
        $end = $request->query->get('end', null);

        return $this->json($report->getCompletedProductionTasksSummary(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null
        ));
    }
}
