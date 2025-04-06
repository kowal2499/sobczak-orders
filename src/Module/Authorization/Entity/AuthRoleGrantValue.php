<?php

namespace App\Module\Authorization\Entity;

use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\ValueObject\GrantValue;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthRoleGrantValueRepository::class)]
#[ORM\Table(
    name: "auth_role_grant_value",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_role_grant_value", columns: ["role_id", "grant_id"])
])]
class AuthRoleGrantValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: AuthRole::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private AuthRole $role;

    #[ORM\ManyToOne(targetEntity: AuthGrant::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private AuthGrant $grant;

    #[ORM\Column(type: 'grant_value', nullable: false)]
    private GrantValue $value;

    /**
     * @param AuthRole $role
     * @param AuthGrant $grant
     * @param GrantValue $value
     */
    public function __construct(AuthRole $role, AuthGrant $grant, GrantValue $value)
    {
        $this->role = $role;
        $this->grant = $grant;
        $this->value = $value;
    }


}