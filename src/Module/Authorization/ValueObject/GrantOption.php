<?php

namespace App\Module\Authorization\ValueObject;

class GrantOption
{
    public function __construct(
        private readonly string $label,
        private readonly string $optionSlug,
    ) {
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getOptionSlug(): string
    {
        return $this->optionSlug;
    }
}
