<?php

/** @author: Roman Kowalski */

namespace App\Module\Tag\Command;

class AssignTagsCommand
{
    public function __construct(
        private readonly array $tags,
        private readonly int $contextId,
        private readonly string $module,
        private readonly int $userId,
    ) {
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
