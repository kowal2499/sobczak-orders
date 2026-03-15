<?php

/** @author: Roman Kowalski */

namespace App\Module\Tag\Command;

use App\Module\Tag\DTO\TagDefinitionDTO;

class UpdateTagDefinitionCommand
{
    public function __construct(
        private readonly int $id,
        private readonly TagDefinitionDTO $tagDefinitionDTO
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTagDefinitionDTO(): TagDefinitionDTO
    {
        return $this->tagDefinitionDTO;
    }
}
