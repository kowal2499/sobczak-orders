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

    private function isStartDelayed(Production $task, ?int $initialStatus): ?bool
    {
        // if dateStart is not specified do not make changes
        if (null === $task->getDateStart()) {
            return $task->getIsStartDelayed();
        }

        // if task status is not started, not applicable or completed, then start is not delayed
        if (true === in_array(
            (int) $task->getStatus(),
            [
                TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
                TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE,
                TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
                TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED,
                TaskTypes::TYPE_CUSTOM_STATUS_AWAITS
            ])
        ) {
            return false;
        }

        if (true === in_array($initialStatus, [
                null,
                TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
                TaskTypes::TYPE_CUSTOM_STATUS_AWAITS,
                TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE,
                TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
                TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED,
            ])) {
            $taskStartDate = new \DateTime($task->getDateStart()->format('Y-m-d'));
            $result = $this->dateTimeHelper->today() > $taskStartDate;
        } else {
            $result = $task->getIsStartDelayed();
        }

        return $result;
    }

    public function setStatus(Production $task, int $newStatus): Production
    {
        $taskType = TaskTypes::getTaskTypeBySlug($task->getDepartmentSlug());
        if (false === in_array($newStatus, TaskTypes::getStatusesByTaskType($taskType))) {
            throw new StatusNotMatchWithTaskTypeException();
        }

        $initialStatus = $task->getStatus();

        // set status
        $task->setStatus($newStatus);

        // set isCompleted flag
        $isCompleted = $this->isCompleted($task, $taskType);
        $task->setIsCompleted($isCompleted);
        $task->setCompletedAt($isCompleted ? (new \DateTime()) : null);

        // set isStartDelayed flag
        $task->setIsStartDelayed($this->isStartDelayed($task, $initialStatus));

        return $task;
    }
}