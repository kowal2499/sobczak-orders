<?php

namespace App\Module\Reports\Production\DTO;

class CustomerDTO
{
    public function __construct(
        private readonly ?string $name = null,
        private readonly ?int $id = null,
    ) {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
