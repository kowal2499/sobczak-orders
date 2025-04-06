<?php

namespace App\Module\Authorization\ValueObject;

class GrantOption
{
    public function __construct(
        private readonly string $label,
        private readonly string $value,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
