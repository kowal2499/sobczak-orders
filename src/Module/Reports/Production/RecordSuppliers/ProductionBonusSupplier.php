<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;
use App\Repository\AgreementLineRepository;

class ProductionBonusSupplier extends BaseSupplier implements RecordSupplierInterface
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
        return 'production_bonus_supplier';
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
        $rows = $this->repository->getProductions($start, $end);
        return $this->transformRows($rows, FactorSource::FACTOR_ADJUSTMENT_BONUS);
    }
}