<?php

namespace App\Module\Reports\Production\Controller;

use App\Controller\BaseController;
use App\Module\Reports\Production\RecordSuppliers\OrdersFinishedRecordSupplier;
use App\Module\Reports\Production\RecordSuppliers\OrdersPendingRecordSupplier;
use App\Module\Reports\Production\RecordSuppliers\ProductionBonusSupplier;
use App\Module\Reports\Production\RecordSuppliers\ProductionCapacitySupplier;
use App\Module\Reports\Schedule\DTO\ScheduleCapacityDTO;
use App\Module\Reports\Schedule\Service\ScheduleCapacityService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionReportsController extends BaseController
{

    #[Route(path: '/agreement-line-production-summary', methods: ['GET'])]
    public function agreementLinesProductionSummary(
        Request $request,
        OrdersPendingRecordSupplier $ordersPendingRecordSupplier,
        OrdersFinishedRecordSupplier $ordersFinishedRecordSupplier,
    ): Response {
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
    ): Response {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        return $this->json($ordersFinishedRecordSupplier->getRecords($start, $end));
    }

    #[Route(path: '/production-pending-details', methods: ['GET'])]
    public function productionPendingDetails(
        Request $request,
        OrdersPendingRecordSupplier $ordersPendingRecordSupplier
    ): Response {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        return $this->json($ordersPendingRecordSupplier->getRecords($start, $end));
    }

    #[Route(path: '/production-tasks-completion-summary', methods: ['GET'])]
    public function productionTasksCompletionSummary(
        Request $request,
        ProductionBonusSupplier $supplier
    ): Response {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        return $this->json($supplier->getRecords($start, $end));
    }

    #[Route(path: '/production-capacity', methods: ['GET'])]
    public function productionCapacity(
        Request $request,
        ProductionCapacitySupplier $supplier
    ): Response {
        $start = new \DateTimeImmutable($request->query->get('start'));
        $end = new \DateTimeImmutable($request->query->get('end'));

        return $this->json($supplier->getRecords($start, $end));
    }

    #[Route(path: '/week-capacity-schedule', methods: ['GET'])]
    public function capacitySchedule(
        Request $request,
        ScheduleCapacityService $service
    ): Response
    {
        $startStr = $request->query->get('startDate');
        $endStr = $request->query->get('endDate');

        try {
            if (!$startStr || !$endStr) {
                throw new \InvalidArgumentException('startDate and endDate are required');
            }

            $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $startStr);
            $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $endStr);

            if (!$start || $start->format('Y-m-d') !== $startStr) {
                throw new \InvalidArgumentException('Invalid startDate format. Expected Y-m-d');
            }
            if (!$end || $end->format('Y-m-d') !== $endStr) {
                throw new \InvalidArgumentException('Invalid endDate format. Expected Y-m-d');
            }

            if ($start > $end) {
                throw new \InvalidArgumentException('startDate must be before endDate');
            }
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(
            array_map(
                fn(ScheduleCapacityDTO $capacityDTO) => $capacityDTO->toArray(),
                $service->calculateBurnout($start, $end)
            ),
            Response::HTTP_OK
        );
    }
}
