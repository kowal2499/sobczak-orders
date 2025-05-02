<?php

namespace App\Module\Authorization\ValueObject;

class GrantValue
{

    private GrantVO $grantVO;
    private bool $value;

    public function __construct(GrantVO $grant, bool $value = true)
    {
        $this->grantVO = $grant;
        $this->value = $value;
    }

    public function getGrantVO(): GrantVO
    {
        return $this->grantVO;
    }

    public function getValue(): bool
    {
        return $this->value;
    }


}