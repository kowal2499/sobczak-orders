<?php

namespace App\Module\ActivityLog\Query\Helper;

use App\Module\ActivityLog\ReadModel\LogFieldReadModel;
use App\Module\ActivityLog\ReadModel\LogModel;

/**
 * Kształt logu w odpowiedzi API. Wspólny dla wszystkich endpointów oddających dziennik,
 * żeby dodanie pola nie wymagało obejścia kilku kontrolerów.
 */
class LogResponseMapper
{
    /**
     * @param LogModel[] $items
     * @return array<string, mixed>
     */
    public function toPage(array $items, int $total, int $page, int $pageSize): array
    {
        return [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'items' => array_map(fn (LogModel $log) => $this->toArray($log), $items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(LogModel $log): array
    {
        return [
            'id' => $log->id,
            'type' => $log->type,
            'content' => $log->content,
            'contentParams' => $log->contentParams,
            'date' => $log->date->format(\DateTimeInterface::ATOM),
            'level' => $log->level->value,
            'priority' => $log->priority->value,
            'user' => $log->user === null ? null : [
                'id' => $log->user->id,
                'name' => $log->user->name,
            ],
            'fields' => array_map(
                static fn (LogFieldReadModel $f) => ['name' => $f->name, 'value' => $f->value],
                $log->fields,
            ),
        ];
    }
}
