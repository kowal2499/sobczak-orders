<?php

namespace App\Module\ActivityLog\Event;

class ActivityLogWasAddedEvent
{
    /**
     * @param array<string, string> $logFields
     */
    public function __construct(
        private readonly int $logId,
        private readonly string $logType,
        private readonly \DateTimeInterface $createdAt,
        private readonly array $logFields,
    ) {
    }

    public function getLogId(): int
    {
        return $this->logId;
    }

    public function getLogType(): string
    {
        return $this->logType;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return array<string, string>
     */
    public function getLogFields(): array
    {
        return $this->logFields;
    }
}
