<?php
/** @author: Roman Kowalski */

namespace App\Message;

use App\DTO\TagDefinitionDTO;

class CreateTagDefinition
{
    private $tagDefinitionDTO;

    /**
     * CreateTagDefinition constructor.
     * @param TagDefinitionDTO $tagDefinitionDTO
     */
    public function __construct(TagDefinitionDTO $tagDefinitionDTO)
    {
        $this->tagDefinitionDTO = $tagDefinitionDTO;
    }

    /**
     * @return TagDefinitionDTO
     */
    public function getTagDefinitionDTO(): TagDefinitionDTO
    {
        return $this->tagDefinitionDTO;
    }
}