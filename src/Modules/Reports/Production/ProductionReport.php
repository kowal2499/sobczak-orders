<?php

namespace App\Modules\Reports\Production;

use App\Module\Production\Factor\BonusFactorCollection;
use App\Module\Production\Factor\ProductionFactorCollection;
use App\Modules\Reports\Production\Mapper\TaskCompletedRecordMapper;
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
    private TaskCompletedRecordMapper $mapper;
    private DoctrineProductionTasksRepository $productionTasksRepository;
    private ProductionFactorCollection $productionFactorCollection;
    private BonusFactorCollection $bonusFactorCollection;

    /**
     * @param DoctrineProductionPendingRepository $productionPendingRepository
     * @param DoctrineProductionFinishedRepository $productionFinishedRepository
     * @param DoctrineProductionTasksRepository $productionTasksRepository
     * @param TaskCompletedRecordMapper $mapper
     * @param ProductionFactorCollection $productionFactorCollection
     */
    public function __construct(
        DoctrineProductionPendingRepository $productionPendingRepository,
        DoctrineProductionFinishedRepository $productionFinishedRepository,
        DoctrineProductionTasksRepository $productionTasksRepository,
        TaskCompletedRecordMapper $mapper,
        ProductionFactorCollection $productionFactorCollection,
        BonusFactorCollection $bonusFactorCollection,
    )
    {
        $this->ordersPendingSupplier = new OrdersPendingRecordSupplier($productionPendingRepository);
        $this->ordersFinishedSupplier = new OrdersFinishedRecordSupplier($productionFinishedRepository);
        $this->suppliers = [$this->ordersPendingSupplier, $this->ordersFinishedSupplier];
        $this->mapper = $mapper;
        $this->productionTasksRepository = $productionTasksRepository;
        $this->productionFactorCollection = $productionFactorCollection;
        $this->bonusFactorCollection = $bonusFactorCollection;
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

    public function getCompletedProductionTasksSummaryInProductionContext(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        $tasksSupplier = new TasksCompletedByDepartmentSupplier(
            $this->productionTasksRepository,
            $this->mapper,
            $this->productionFactorCollection,
        );

        return $tasksSupplier->getSummary($start, $end);
    }

    public function getCompletedProductionTasksSummaryInBonusContext(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
    {
        $tasksSupplier = new TasksCompletedByDepartmentSupplier(
            $this->productionTasksRepository,
            $this->mapper,
            $this->bonusFactorCollection,
        );

        return $tasksSupplier->getSummary($start, $end);
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