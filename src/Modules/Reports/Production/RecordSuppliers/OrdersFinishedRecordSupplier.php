<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Modules\Reports\Production\Repository\DoctrineProductionFinishedRepository;

class OrdersFinishedRecordSupplier implements RecordSupplierInterface
{
    private $repository;

    public function __construct(DoctrineProductionFinishedRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getTitle(): string
    {
        return 'Zamówienia zrealizowane';
    }

    public function getRecords(?\DateTimeInterface $start, ?\DateTimeInterface $end, array $departments = []): array
    {
        return $this->repository->getDetails($start, $end, $departments);
    }

    public function getSummary(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
    {
        return $this->repository->getSummary($start, $end);
    }

    public function getId(): string
    {
        return 'orders_finished';
    }
}