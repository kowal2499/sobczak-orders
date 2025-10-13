<?php

namespace App\Module\Authorization\Command;

class DeleteRoleGrantValue
{
    public function __construct(
        private readonly int     $roleId,
        private readonly int     $grantId,
        private readonly ?string $grantOptionSlug,
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
}