<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Module\Production\Repository\FactorRepository;
use App\Modules\Reports\Production\Mapper\TaskCompletedRecordMapper;
use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;
use App\Repository\AgreementLineRepository;

class TasksCompletedByDepartmentSupplier implements RecordSupplierInterface
{
    public function __construct(
        private readonly DoctrineProductionTasksRepository $tasksRepository,
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly TaskCompletedRecordMapper $mapper,
        private readonly FactorCalculator $factorCalculator
    ) {
    }

    public function getId(): string
    {
        return 'tasks-completion';
    }

    public function getTitle(): string
    {
        return 'Zamówienia zrealizowane wg działu';
    }

    public function getRecords(?\DateTimeInterface $start, ?\DateTimeInterface $end, array $departments = []): array
    {
        return [];
    }

    public function getSummary(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
    {
        $rows = $this->tasksRepository->getProductions($start, $end);
        $agreementLineIds = [];
        $result = [];

        foreach ($rows as $row) {
            if (!in_array($row['id'], array_keys($agreementLineIds))) {
                $agreementLineIds[$row['id']] = null;
            }
        }

        if (!empty(array_keys($agreementLineIds))) {
            foreach ($this->agreementLineRepository->findWithFactors(array_keys($agreementLineIds)) as $agreementLine) {
                $agreementLineIds[$agreementLine->getId()] = $agreementLine;
            }
        }

        foreach ($rows as $row) {
            $item = $this->mapper->mapRow($row);

            if (isset($agreementLineIds[$row['id']])) {
                $agreementLine = $agreementLineIds[$row['id']];

                $calcFactor = $this->factorCalculator->calculate(
                    $agreementLine,
                    $row['departmentSlug'] ?? null,
                    $agreementLine->getFactors()->toArray(),
                    FactorSource::FACTOR_ADJUSTMENT_BONUS
                );
                $item->setFactors($calcFactor->toArray());
            }


            // todo: factors disabled
//            $item->setFactors(
//                $this->factorCollection->getFactors(
//                    $item->getAgreementLine()->getId(),
//                    $item->getDepartmentSlug()
//                )
//            );
            $result[] = $item;


        }



        return $result;
    }
}