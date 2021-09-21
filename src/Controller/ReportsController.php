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
     * @Route("/agreement-line-production", methods={"POST"})
     */
    public function agreementLinesProduction(Request $request, ProductionReport $report): Response
    {
        $start = $request->request->get('start');
        $end = $request->request->get('end');
        $departments = $request->request->get('departments', []);

        return $this->json($report->calc(
            $start ? new \DateTime($start) : null,
            $end ? new \DateTime($end) : null,
            $departments
        ));
    }
}
