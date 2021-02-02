<?php
/** @author: Roman Kowalski */

namespace App\Message;

class AssignTags
{
    private $tags;
    private $contextId;
    private $module;
    private $userId;

    public function __construct(array $tags, int $contextId, string $module, int $userId)
    {
        $this->tags = $tags;
        $this->contextId = $contextId;
        $this->module = $module;
        $this->userId = $userId;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return mixed
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

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }
}