<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Modules\Reports\Production\Mapper\TaskCompletedRecordMapper;
use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;

class TasksCompletedByDepartmentSupplier implements RecordSupplierInterface
{
    public function __construct(
        private readonly DoctrineProductionTasksRepository $tasksRepository,
        private readonly TaskCompletedRecordMapper $mapper,
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

        $result = [];
        foreach ($rows as $row) {
            $item = $this->mapper->mapRow($row);
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