<?php

namespace App\Controller;

use App\Modules\Reports\Production\ProductionReport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/reports")
 */
class ReportsController extends BaseController
{
    /**
     * @Route("/agreement-line-production", methods={"GET"})
     * @deprecated
     */
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

    /**
     * @Route("/agreement-line-production-summary", methods={"GET"})
     */
    public function agreementLinesProductionSummary(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->json($report->getSummary(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null
        ));
    }

    /**
     * @Route("/production-finished-details", methods={"GET"})
     */
    public function productionFinishedDetails(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->json($report->getOrdersFinishedDetails(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null
        ));
    }

    /**
     * @Route("/production-pending-details", methods={"GET"})
     */
    public function productionPendingDetails(Request $request, ProductionReport $report): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        return $this->json($report->getOrdersPendingDetails(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null
        ));
    }

    /**
     * @Route("/production-tasks-completion-summary", methods={"GET"})
     */
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
