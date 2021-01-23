<?php
/** @author: Roman Kowalski */

namespace App\Message;

class DeleteTagDefinition
{
    private $id;

    /**
     * DeleteTagDefinition constructor.
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}