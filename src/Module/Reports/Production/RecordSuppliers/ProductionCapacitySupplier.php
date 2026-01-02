<?php

namespace App\Module\Reports\Production\RecordSuppliers;

use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Module\Reports\Production\RecordSupplierInterface;
use App\Module\Reports\Production\Repository\DoctrineProductionTasksRepository;
use App\Repository\AgreementLineRepository;

class ProductionCapacitySupplier extends BaseSupplier implements RecordSupplierInterface
{
    private DoctrineProductionTasksRepository $repository;

    public function __construct(
        DoctrineProductionTasksRepository $repository,
        AgreementLineRepository $agreementLineRepository,
        FactorCalculator $factorCalculator,
    ) {
        parent::__construct($agreementLineRepository, $factorCalculator);
        $this->repository = $repository;
    }

    public function getId(): string
    {
        return 'production_capacity_supplier';
    }

    public function getTitle(): string
    {
        return '';
    }

    public function getSummary(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
    {
        return [];
    }

    public function getRecords(?\DateTimeInterface $start, ?\DateTimeInterface $end, array $departments = []): array
    {
        if (!$start || !$end) {
            return [];
        }
        $rows = $this->repository->getCapacityInTime($start, $end);
        return $this->transformRows($rows, FactorSource::FACTOR_ADJUSTMENT_RATIO);
    }
}