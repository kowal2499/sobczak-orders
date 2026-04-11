<?php

namespace App\Module\Task\DTO;

use App\Module\Task\Entity\Task;

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
        );
    }
}
