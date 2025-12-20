<?php

namespace App\Module\Reports\Production\Controller;

use App\Controller\BaseController;
use App\Module\Reports\Production\RecordSuppliers\OrdersFinishedRecordSupplier;
use App\Module\Reports\Production\RecordSuppliers\OrdersPendingRecordSupplier;
use App\Module\Reports\Production\RecordSuppliers\ProductionBonusSupplier;
use App\Modules\Reports\Production\ProductionReport;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionReportsController extends BaseController
{
    /**
     * @deprecated
     */
//    #[Route(path: '/agreement-line-production', methods: ['GET'])]
//    public function agreementLinesProduction(Request $request, ProductionReport $report): Response
//    {
//        $start = $request->query->get('start');
//        $end = $request->query->get('end');
//        $departments = $request->query->get('departments', []);
//
//        return $this->json($report->calc(
//            $start ? new \DateTime($start) : null,
//            $end ? new \DateTime($end) : null,
//            $departments
//        ));
//    }

    #[Route(path: '/agreement-line-production-summary', methods: ['GET'])]
    public function agreementLinesProductionSummary(
        Request $request,
        OrdersPendingRecordSupplier $ordersPendingRecordSupplier,
        OrdersFinishedRecordSupplier $ordersFinishedRecordSupplier,
    ): Response
    {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        $response = [];
        foreach ([$ordersPendingRecordSupplier, $ordersFinishedRecordSupplier] as $supplier) {
            $response[$supplier->getId()] = $supplier->getSummary($start, $end);
        }

        return $this->json($response);
    }

    #[Route(path: '/production-finished-details', methods: ['GET'])]
    public function productionFinishedDetails(
        Request $request,
        OrdersFinishedRecordSupplier $ordersFinishedRecordSupplier
    ): Response
    {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        return $this->json($ordersFinishedRecordSupplier->getRecords($start, $end));
    }

    #[Route(path: '/production-pending-details', methods: ['GET'])]
    public function productionPendingDetails(
        Request $request,
        OrdersPendingRecordSupplier $ordersPendingRecordSupplier
    ): Response
    {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        return $this->json($ordersPendingRecordSupplier->getRecords($start, $end));
    }

    #[Route(path: '/production-tasks-completion-summary', methods: ['GET'])]
    public function productionTasksCompletionSummary(Request $request, ProductionBonusSupplier $supplier): Response
    {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        return $this->json($supplier->getRecords($start, $end));
    }
}
