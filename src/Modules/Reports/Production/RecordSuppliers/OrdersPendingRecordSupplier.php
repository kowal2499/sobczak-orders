<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Repository\AgreementLineRepository;
use App\Repository\ProductionRepository;

class OrdersPendingRecordSupplier implements RecordSupplierInterface
{
    private $repository;

    public function __construct(AgreementLineRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getTitle(): string
    {
        return 'Zamówienia w realizacji';
    }

    public function getRecords(?\DateTimeInterface $start, ?\DateTimeInterface $end, array $departments = []): array
    {
        if ($departments) {
            return $this->repository->getWithProductionPendingByDepartment($start, $end, $departments);
        }
        return $this->repository->getWithProductionPending($start, $end);
    }

    public function getId(): string
    {
        return 'orders_pending';
    }
}