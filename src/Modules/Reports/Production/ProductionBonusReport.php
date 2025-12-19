<?php

namespace App\Modules\Reports\Production;

use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Modules\Reports\Production\DTO\TaskCompletedRecordDTO;
use App\Modules\Reports\Production\Mapper\TaskCompletedRecordMapper;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;
use App\Repository\AgreementLineRepository;

class ProductionBonusReport
{

    public function __construct(
        private readonly DoctrineProductionTasksRepository $tasksRepository,
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly TaskCompletedRecordMapper $mapper,
        private readonly FactorCalculator $factorCalculator
    ) {
    }

    /**
     * @param \DateTimeInterface|null $start
     * @param \DateTimeInterface|null $end
     * @return TaskCompletedRecordDTO[]
     */
    public function getData(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array
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
            $result[] = $item;
        }
        return $result;
    }
}