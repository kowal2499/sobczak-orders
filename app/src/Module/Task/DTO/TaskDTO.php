<?php

namespace App\Module\Task\DTO;

use App\Module\Task\Entity\Task;
use App\Module\Task\Entity\TaskStatusLog;

class TaskDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly int $status,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $dateStart,
        public readonly ?string $dateEnd,
        public readonly int $agreementLineId,
        public readonly array $statusLogs,
    ) {
    }

    public static function fromEntity(Task $task): self
    {
        return new self(
            id: $task->getId(),
            type: $task->getType(),
            status: $task->getStatus(),
            title: $task->getTitle(),
            description: $task->getDescription(),
            dateStart: $task->getDateStart()?->format('Y-m-d'),
            dateEnd: $task->getDateEnd()?->format('Y-m-d'),
            agreementLineId: $task->getAgreementLine()->getId(),
            statusLogs: array_map(
                fn(TaskStatusLog $log) => [
                    'id' => $log->getId(),
                    'currentStatus' => $log->getCurrentStatus(),
                    'previousStatus' => $log->getPreviousStatus(),
                    'createdAt' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
                    'user' => $log->getUser() ? [
                        'id' => $log->getUser()->getId(),
                        'userFullName' => $log->getUser()->getUserFullName(),
                    ] : null,
                ],
                $task->getStatusLogs()->toArray()
            ),
        );
    }
}
