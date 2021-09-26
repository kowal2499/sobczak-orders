<?php

namespace App\Modules\Reports\Production;

use App\Modules\Reports\Production\RecordSuppliers\OrdersFinishedRecordSupplier;
use App\Modules\Reports\Production\RecordSuppliers\OrdersPendingRecordSupplier;
use App\Modules\Reports\Production\Repository\DoctrineProductionFinishedRepository;
use App\Modules\Reports\Production\Repository\DoctrineProductionPendingRepository;
use App\Repository\AgreementLineRepository;

class ProductionReport
{
    /** @var RecordSupplierInterface[] */
    private $suppliers;

    /**
     * @param DoctrineProductionPendingRepository $productionPendingRepository
     * @param DoctrineProductionFinishedRepository $productionFinishedRepository
     */
    public function __construct(
        DoctrineProductionPendingRepository $productionPendingRepository,
        DoctrineProductionFinishedRepository $productionFinishedRepository
    )
    {
        $this->suppliers[] = new OrdersPendingRecordSupplier($productionPendingRepository);
        $this->suppliers[] = new OrdersFinishedRecordSupplier($productionFinishedRepository);
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