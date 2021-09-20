<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Repository\AgreementLineRepository;

class OrdersFinishedRecordSupplier implements RecordSupplierInterface
{
    private $repository;

    public function __construct(AgreementLineRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getTitle(): string
    {
        return 'Zamówienia zrealizowane';
    }

    public function getRecords(\DateTimeInterface $start, \DateTimeInterface $end, array $departments = []): array
    {
        if ($departments) {
            return $this->repository->getWithProductionFinishedByDepartment($start, $end, $departments);
        }
        return $this->repository->getWithProductionFinished($start, $end);
    }

    public function getId(): string
    {
        return 'orders_finished';
    }
}