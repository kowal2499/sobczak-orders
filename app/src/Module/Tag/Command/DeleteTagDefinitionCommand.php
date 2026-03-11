<?php

/** @author: Roman Kowalski */

namespace App\Module\Tag\Command;

class DeleteTagDefinitionCommand
{
    public function __construct(
        private readonly int $id
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
