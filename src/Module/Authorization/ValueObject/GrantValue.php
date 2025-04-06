<?php

namespace App\Module\Authorization\ValueObject;

class GrantValue
{

    /** @var string[]|boolean */
    private mixed $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getRawValue(): mixed
    {
        return $this->value;
    }
}