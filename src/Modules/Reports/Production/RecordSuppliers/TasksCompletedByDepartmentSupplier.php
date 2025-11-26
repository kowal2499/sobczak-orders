<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Modules\Reports\Production\Mapper\TaskCompletedRecordMapper;
use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;

class TasksCompletedByDepartmentSupplier implements RecordSupplierInterface
{
    private $tasksRepository;
    private TaskCompletedRecordMapper $mapper;

    public function __construct(DoctrineProductionTasksRepository $tasksRepository, TaskCompletedRecordMapper $mapper)
    {
        $this->tasksRepository = $tasksRepository;
        $this->mapper = $mapper;
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
        return $this->mapper->mapMany($rows);
    }
}