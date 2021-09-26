<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Modules\Reports\Production\Repository\DoctrineProductionPendingRepository;

class OrdersPendingRecordSupplier implements RecordSupplierInterface
{
    private $repository;

    public function __construct(DoctrineProductionPendingRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getTitle(): string
    {
        return 'Zamówienia w realizacji';
    }

    public function getRecords(?\DateTimeInterface $start, ?\DateTimeInterface $end, array $departments = []): array
    {
        return $this->repository->getDetails(null, $end, $departments);
    }

    public function getSummary(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
    {
        return $this->repository->getSummary(null, $end);
    }

    public function getId(): string
    {
        return 'orders_pending';
    }
}