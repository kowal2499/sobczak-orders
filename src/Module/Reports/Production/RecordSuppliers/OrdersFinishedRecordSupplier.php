<?php

namespace App\Module\Reports\Production\RecordSuppliers;

use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Module\Reports\Production\RecordSupplierInterface;
use App\Module\Reports\Production\Repository\DoctrineProductionFinishedRepository;
use App\Repository\AgreementLineRepository;

class OrdersFinishedRecordSupplier extends BaseSupplier implements RecordSupplierInterface
{
    private DoctrineProductionFinishedRepository $repository;

    public function __construct(
        DoctrineProductionFinishedRepository $repository,
        AgreementLineRepository $agreementLineRepository,
        FactorCalculator $factorCalculator,
    ) {
        parent::__construct($agreementLineRepository, $factorCalculator);
        $this->repository = $repository;
    }

    public function getTitle(): string
    {
        return 'Zamówienia zrealizowane';
    }

    public function getRecords(?\DateTimeInterface $start, ?\DateTimeInterface $end, array $departments = []): array
    {
        $rows = $this->repository->getDetails($start, $end);
        return $this->transformRows($rows, FactorSource::FACTOR_ADJUSTMENT_RATIO);
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