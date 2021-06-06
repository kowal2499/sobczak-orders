<?php

namespace App\Service\Production;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Exceptions\Production\StatusNotMatchWithTaskTypeException;

class TaskStatusService
{
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

    private function isStartDelayed(Production $task, string $type): bool
    {
        return true;
    }

    public function setStatus(Production $task, int $newStatus): Production
    {
        $taskType = TaskTypes::getTaskTypeBySlug($task->getDepartmentSlug());
        if (false === in_array($newStatus, TaskTypes::getStatusesByTaskType($taskType))) {
            throw new StatusNotMatchWithTaskTypeException();
        }

        $task->setStatus($newStatus);
        $task->setIsCompleted(
            $this->isCompleted($task, $taskType)
        );
        $task->setIsStartDelayed(
            $this->isStartDelayed($task, $taskType)
        );

        return $task;
    }
}