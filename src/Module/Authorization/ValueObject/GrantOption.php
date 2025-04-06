<?php

namespace App\Module\Authorization\ValueObject;

class GrantOption
{
    public function __construct(
        private readonly string $name,
        private readonly string $value
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
