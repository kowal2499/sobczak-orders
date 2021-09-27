<?php

namespace App\Modules\Reports\Production;

use App\Modules\Reports\Production\RecordSuppliers\OrdersFinishedRecordSupplier;
use App\Modules\Reports\Production\RecordSuppliers\OrdersPendingRecordSupplier;
use App\Modules\Reports\Production\Repository\DoctrineProductionFinishedRepository;
use App\Modules\Reports\Production\Repository\DoctrineProductionPendingRepository;

class ProductionReport
{
    /** @var RecordSupplierInterface[] */
    private $suppliers;
    /** @var OrdersPendingRecordSupplier */
    private $ordersPendingSupplier;
    /** @var OrdersFinishedRecordSupplier */
    private $ordersFinishedSupplier;

    /**
     * @param DoctrineProductionPendingRepository $productionPendingRepository
     * @param DoctrineProductionFinishedRepository $productionFinishedRepository
     */
    public function __construct(
        DoctrineProductionPendingRepository $productionPendingRepository,
        DoctrineProductionFinishedRepository $productionFinishedRepository
    )
    {
        $this->ordersPendingSupplier = new OrdersPendingRecordSupplier($productionPendingRepository);
        $this->ordersFinishedSupplier = new OrdersFinishedRecordSupplier($productionFinishedRepository);
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