<?php

namespace App\Module\Authorization\Command;

class CreateRoleGrantValue
{
    public function __construct(
        private readonly int     $roleId,
        private readonly int     $grantId,
        private readonly ?string $grantOptionSlug,
        private readonly bool    $value
    ){
    }

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function getGrantId(): int
    {
        return $this->grantId;
    }

    public function getGrantOptionSlug(): ?string
    {
        return $this->grantOptionSlug;
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}