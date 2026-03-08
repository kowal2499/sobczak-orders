<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\Query;

class GetAssignedTagsQuery
{
    public function __construct(
        private readonly int $contextId,
        private readonly string $module
    ) {
    }

    public function getContextId(): int
    {
        return $this->contextId;
    }

    public function getModule(): string
    {
        return $this->module;
    }
}
