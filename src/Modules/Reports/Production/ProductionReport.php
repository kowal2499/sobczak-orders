<?php

namespace App\Modules\Reports\Production;

use App\Modules\Reports\Production\RecordSuppliers\OrdersFinishedRecordSupplier;
use App\Modules\Reports\Production\RecordSuppliers\OrdersPendingRecordSupplier;
use App\Modules\Reports\Production\RecordSuppliers\TasksCompletedByDepartmentSupplier;
use App\Modules\Reports\Production\Repository\DoctrineProductionFinishedRepository;
use App\Modules\Reports\Production\Repository\DoctrineProductionPendingRepository;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;

class ProductionReport
{
    /** @var RecordSupplierInterface[] */
    private $suppliers;
    /** @var OrdersPendingRecordSupplier */
    private $ordersPendingSupplier;
    /** @var OrdersFinishedRecordSupplier */
    private $ordersFinishedSupplier;
    /** @var TasksCompletedByDepartmentSupplier */
    private $tasksSupplier;

    /**
     * @param DoctrineProductionPendingRepository $productionPendingRepository
     * @param DoctrineProductionFinishedRepository $productionFinishedRepository
     * @param DoctrineProductionTasksRepository $productionTasksRepository
     */
    public function __construct(
        DoctrineProductionPendingRepository $productionPendingRepository,
        DoctrineProductionFinishedRepository $productionFinishedRepository,
        DoctrineProductionTasksRepository $productionTasksRepository
    )
    {
        $this->ordersPendingSupplier = new OrdersPendingRecordSupplier($productionPendingRepository);
        $this->ordersFinishedSupplier = new OrdersFinishedRecordSupplier($productionFinishedRepository);
        $this->tasksSupplier = new TasksCompletedByDepartmentSupplier($productionTasksRepository);
        $this->suppliers = [$this->ordersPendingSupplier, $this->ordersFinishedSupplier];
    }

    public function getSummary(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        $result = [];
        foreach ($this->suppliers as $supplier) {
            $result[$supplier->getId()] = [
                $supplier->getSummary($start, $end)
            ];
        }
        return $result;
    }

    public function getOrdersPendingDetails(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        return $this->ordersPendingSupplier->getRecords($start, $end);
    }

    public function getOrdersFinishedDetails(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        return $this->ordersFinishedSupplier->getRecords($start, $end);
    }

    public function getCompletedProductionTasksSummary(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        return $this->tasksSupplier->getSummary($start, $end);
    }

    /**
     * @deprecated
     */
    public function calc(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end,
        array $departments = []
    ): array {
        $result = [];
        foreach ($this->suppliers as $supplier) {
            $result[$supplier->getId()] = [
                'title' => $supplier->getTitle(),
                'data' => $supplier->getRecords($start, $end, $departments)
            ];
        }
        return $result;
    }
}