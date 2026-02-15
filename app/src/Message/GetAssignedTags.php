<?php
/** @author: Roman Kowalski */

namespace App\Message;

class GetAssignedTags
{
    private $contextId;
    private $module;

    public function __construct(int $contextId, string $module)
    {
        $this->contextId = $contextId;
        $this->module = $module;
    }

    /**
     * @return int
     */
    public function getContextId(): int
    {
        return $this->contextId;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

}