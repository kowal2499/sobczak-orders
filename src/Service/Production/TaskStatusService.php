<?php

namespace App\Service\Production;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Exceptions\Production\StatusNotMatchWithTaskTypeException;
use App\Service\DateTimeHelper;

class TaskStatusService
{
    private $dateTimeHelper;

    public function __construct(DateTimeHelper $dateTimeHelper)
    {
        $this->dateTimeHelper = $dateTimeHelper;
    }
    private function isCompleted(Production $task, string $type): bool
    {
        if ($type === TaskTypes::TYPE_DEFAULT) {
            return (int) $task->getStatus() === TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED;
        }

        if ($type === TaskTypes::TYPE_CUSTOM) {
            return (int) $task->getStatus() === TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED;
        }

        return false;
    }

    private function isStartDelayed(Production $task): ?bool
    {
        // if dateStart is not specified do not make changes
        if (null === $task->getDateStart()) {
            return $task->getIsStartDelayed();
        }

        // if task status is not applicable or completed, then return false
        if (true === in_array(
            (int) $task->getStatus(),
            [TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED])
        ) {
            return false;
        }

        // if task status is something else than beginning of the process, then do not make changes
        if (false === in_array(
                (int) $task->getStatus(),
                [TaskTypes::TYPE_DEFAULT_STATUS_STARTED, TaskTypes::TYPE_CUSTOM_STATUS_PENDING]
            )
        ) {
            return $task->getIsStartDelayed();
        }

        $taskStartDate = new \DateTime($task->getDateStart()->format('Y-m-d'));

        return $this->dateTimeHelper->today() > $taskStartDate;
    }

    public function setStatus(Production $task, int $newStatus): Production
    {
        $taskType = TaskTypes::getTaskTypeBySlug($task->getDepartmentSlug());
        if (false === in_array($newStatus, TaskTypes::getStatusesByTaskType($taskType))) {
            throw new StatusNotMatchWithTaskTypeException();
        }

        $task->setStatus($newStatus);

        $task->setIsCompleted($this->isCompleted($task, $taskType));

        $task->setIsStartDelayed($this->isStartDelayed($task));

        return $task;
    }
}