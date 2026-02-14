<?php

namespace App\Module\Reports\Production\DTO;

class CustomerDTO
{
    public function __construct(
        private readonly ?string $name = null
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }
}
