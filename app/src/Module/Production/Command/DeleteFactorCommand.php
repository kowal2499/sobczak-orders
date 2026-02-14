<?php

namespace App\Module\Production\Command;

class DeleteFactorCommand
{
    public function __construct(
        private readonly int $factorId,
    ) {
    }

    public function getFactorId(): int
    {
        return $this->factorId;
    }
}