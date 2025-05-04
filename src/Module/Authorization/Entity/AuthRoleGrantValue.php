<?php

namespace App\Module\Authorization\Entity;

use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthRoleGrantValueRepository::class)]
#[ORM\Table(
    name: "auth_role_grant_value",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_role_grant_value", columns: ["role_id", "grant_id", "grant_option_slug"])
])]
class AuthRoleGrantValue extends AuthAbstractGrantValue
{
    #[ORM\ManyToOne(targetEntity: AuthRole::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private AuthRole $role;

    public function __construct(AuthRole $role, AuthGrant $grant, ?string $grantOptionSlug = null)
    {
        parent::__construct($grant, $grantOptionSlug);
        $this->role = $role;
    }

    public function getRole(): AuthRole
    {
        return $this->role;
    }
}