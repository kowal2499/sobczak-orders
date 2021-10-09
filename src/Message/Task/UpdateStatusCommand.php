<?php

namespace App\Message\Task;

class UpdateStatusCommand
{
    private $taskId;
    private $newStatus;

    public function __construct(int $taskId, int $newStatus)
    {
        $this->taskId = $taskId;
        $this->newStatus = $newStatus;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->taskId;
    }

    /**
     * @return int
     */
    public function getNewStatus(): int
    {
        return $this->newStatus;
    }
}