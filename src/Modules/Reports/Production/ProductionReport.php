<?php

namespace App\Modules\Reports\Production;

use App\Modules\Reports\Production\RecordSuppliers\OrdersFinishedRecordSupplier;
use App\Modules\Reports\Production\RecordSuppliers\OrdersPendingRecordSupplier;
use App\Repository\AgreementLineRepository;

class ProductionReport
{
    /** @var RecordSupplierInterface[] */
    private $suppliers;

    /**
     * @param string[] $suppliers
     */
    public function __construct(AgreementLineRepository $repository)
    {
        $this->suppliers[] = new OrdersPendingRecordSupplier($repository);
        $this->suppliers[] = new OrdersFinishedRecordSupplier($repository);
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