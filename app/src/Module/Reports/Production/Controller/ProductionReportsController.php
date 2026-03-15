<?php

namespace App\Module\Reports\Production\Controller;

use App\Controller\BaseController;
use App\Module\Reports\Production\RecordSuppliers\OrdersFinishedRecordSupplier;
use App\Module\Reports\Production\RecordSuppliers\OrdersPendingRecordSupplier;
use App\Module\Reports\Production\RecordSuppliers\ProductionBonusSupplier;
use App\Module\Reports\Production\RecordSuppliers\ProductionCapacitySupplier;
use App\Utilities\DateValidationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionReportsController extends BaseController
{
    use DateValidationTrait;

    #[Route(path: '/agreement-line-production-summary', methods: ['GET'])]
    public function agreementLinesProductionSummary(
        Request $request,
        OrdersPendingRecordSupplier $ordersPendingRecordSupplier,
        OrdersFinishedRecordSupplier $ordersFinishedRecordSupplier,
    ): Response {

        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

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
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json($ordersFinishedRecordSupplier->getRecords($start, $end));
    }

    #[Route(path: '/production-pending-details', methods: ['GET'])]
    public function productionPendingDetails(
        Request $request,
        OrdersPendingRecordSupplier $ordersPendingRecordSupplier
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end'),
            false
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json($ordersPendingRecordSupplier->getRecords($start, $end));
    }

    #[Route(path: '/production-tasks-completion-summary', methods: ['GET'])]
    public function productionTasksCompletionSummary(
        Request $request,
        ProductionBonusSupplier $supplier
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json($supplier->getRecords($start, $end));
    }

    #[Route(path: '/production-capacity', methods: ['GET'])]
    public function productionCapacity(
        Request $request,
        ProductionCapacitySupplier $supplier
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json($supplier->getRecords($start, $end));
    }
}
