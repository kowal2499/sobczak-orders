<?php

namespace App\Module\Reports\Production\RecordSuppliers;

use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Module\Reports\Production\RecordSupplierInterface;
use App\Module\Reports\Production\Repository\DoctrineProductionPendingRepository;
use App\Repository\AgreementLineRepository;

class OrdersPendingRecordSupplier extends BaseSupplier implements RecordSupplierInterface
{
    private DoctrineProductionPendingRepository $repository;

    public function __construct(
        DoctrineProductionPendingRepository $repository,
        AgreementLineRepository $agreementLineRepository,
        FactorCalculator $factorCalculator,
    ) {
        parent::__construct($agreementLineRepository, $factorCalculator);
        $this->repository = $repository;
    }

    public function getTitle(): string
    {
        return 'Zamówienia w realizacji';
    }

    public function getRecords(?\DateTimeInterface $start, ?\DateTimeInterface $end, array $departments = []): array
    {
        $rows = $this->repository->getDetails(null, $end);
        return $this->transformRows($rows, FactorSource::AGREEMENT_LINE);
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