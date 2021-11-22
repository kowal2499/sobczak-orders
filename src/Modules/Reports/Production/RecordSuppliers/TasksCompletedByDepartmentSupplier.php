<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Entity\Definitions\TaskTypes;
use App\Modules\Reports\Production\RecordSupplierInterface;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;

class TasksCompletedByDepartmentSupplier implements RecordSupplierInterface
{
    private $tasksRepository;

    public function __construct(DoctrineProductionTasksRepository $tasksRepository)
    {
        $this->tasksRepository = $tasksRepository;
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
        $perAgreementId = [];
        foreach ($this->tasksRepository->getProductions($start, $end) as $production) {
            if (false === isset($perAgreementId[$production['id']])) {
                $perAgreementId[$production['id']] = [
                    'departmentSlug' => $production['departmentSlug'],
                    'completedAt' => $production['completedAt'],
                    'id' => $production['id'],
                    'factor' => $production['factor'],
                    'productionStartDate' => $production['productionStartDate'],
                    'productionCompletionDate' => $production['productionCompletionDate'],
                    'confirmedDate' => $production['confirmedDate'],
                    'productName' => $production['productName'],
                    'customerName' => $production['customerName'],
                    'orderNumber' => $production['orderNumber'],
                    'dpt01' => false,
                    'dpt02' => false,
                    'dpt03' => false,
                    'dpt04' => false,
                    'dpt05' => false,
                ];
            }
            $perAgreementId[$production['id']][$production['departmentSlug']] = true;
        }

        $perDepartmentSlug = [];
        $departments = TaskTypes::getAll()[TaskTypes::TYPE_DEFAULT];
        foreach ($departments as $department) {
            $perDepartmentSlug[$department['slug']] = [
                'slug' => $department['slug'],
                'name' => $department['name'],
                'factorsSummary' => 0
            ];
        }

        foreach ($perAgreementId as $production) {
            foreach ($perDepartmentSlug as $departmentSlug => $factorSum) {
                if ($production[$departmentSlug]) {
                    $perDepartmentSlug[$departmentSlug]['factorsSummary'] += $production['factor'];
                }
            }
        }

        return [
            'tasksCompleted' => [
                'perAgreement' => array_values($perAgreementId),
                'perDepartment' => array_values($perDepartmentSlug)
            ]
        ];
    }
}