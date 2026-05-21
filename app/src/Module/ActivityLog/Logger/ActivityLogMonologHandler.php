<?php

namespace App\Module\ActivityLog\Logger;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\System\CommandBus;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Bridges PSR-3 logger calls (on the "activity_log" channel) into AddActivityLogCommand
 * dispatches, so producers don't need to know about CQRS.
 *
 * Reserved context keys (extracted, not stored as fields):
 *   - type           → log type (default: "default")
 *   - priority       → LogPriority enum value (default: normal)
 *   - createdDate    → \DateTimeInterface or parseable string
 *   - contentParams  → array of i18n interpolation parameters (presentation only, not queryable)
 *   - impersonateUserId → forwarded into contextData so the command handler can apply it
 */
class ActivityLogMonologHandler extends AbstractProcessingHandler
{
    private const RESERVED_KEYS = ['type', 'priority', 'createdDate', 'contentParams'];

    public function __construct(
        private readonly CommandBus $commandBus,
        int|string $level = Logger::DEBUG,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(array $record): void
    {
        $context = is_array($record['context'] ?? null) ? $record['context'] : [];

        $type = isset($context['type']) ? (string) $context['type'] : 'default';
        $priority = LogPriority::tryFrom((string) ($context['priority'] ?? '')) ?? LogPriority::normal;
        $createdDate = $this->parseCreatedDate($context['createdDate'] ?? null);
        $contentParams = is_array($context['contentParams'] ?? null) ? $context['contentParams'] : null;
        $level = LogLevel::tryFrom((string) ($record['level_name'] ?? '')) ?? LogLevel::INFO;

        $contextData = $context;
        foreach (self::RESERVED_KEYS as $key) {
            unset($contextData[$key]);
        }
        $contextData = $this->flatten($contextData);

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: (string) ($record['message'] ?? ''),
            type: $type,
            contextData: $contextData,
            level: $level,
            authorUserId: null,
            createdDate: $createdDate,
            priority: $priority,
            contentParams: $contentParams,
        ));
    }

    private function parseCreatedDate(mixed $raw): ?\DateTimeInterface
    {
        if ($raw === null) {
            return null;
        }
        if ($raw instanceof \DateTimeInterface) {
            return $raw;
        }
        try {
            return new \DateTime((string) $raw);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @return array<string, string>
     */
    private function flatten(array $context): array
    {
        $out = [];
        foreach ($context as $key => $value) {
            $out[(string) $key] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $out;
    }
}
