<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\Command;

use App\Module\Tag\DTO\TagDefinitionDTO;

class CreateTagDefinitionCommand
{
    public function __construct(
        private readonly TagDefinitionDTO $tagDefinitionDTO
    ) {
    }

    public function getTagDefinitionDTO(): TagDefinitionDTO
    {
        return $this->tagDefinitionDTO;
    }
}
