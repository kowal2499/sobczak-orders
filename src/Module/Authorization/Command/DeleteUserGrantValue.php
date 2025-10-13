<?php

namespace App\Module\Authorization\Command;

class DeleteUserGrantValue
{

    public function __construct(
        private readonly int $userGrantValueId,
    ) {
    }

    public function getUserGrantValueId(): int
    {
        return $this->userGrantValueId;
    }
}