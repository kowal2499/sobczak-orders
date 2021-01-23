<?php
/** @author: Roman Kowalski */

namespace App\Message;

use App\DTO\TagDefinitionDTO;

class UpdateTagDefinition
{
    private $id;

    private $tagDefinitionDTO;

    /**
     * CreateTagDefinition constructor.
     * @param int $id
     * @param TagDefinitionDTO $tagDefinitionDTO
     */
    public function __construct(int $id, TagDefinitionDTO $tagDefinitionDTO)
    {
        $this->id = $id;
        $this->tagDefinitionDTO = $tagDefinitionDTO;
    }

    /**
     * @return TagDefinitionDTO
     */
    public function getTagDefinitionDTO(): TagDefinitionDTO
    {
        return $this->tagDefinitionDTO;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}