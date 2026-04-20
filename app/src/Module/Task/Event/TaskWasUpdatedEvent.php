<?php

namespace App\Module\Task\Event;

class TaskWasUpdatedEvent
{
    public function __construct(
        private readonly int $taskId,
        private readonly int $agreementLineId,
    ) {
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }
}
