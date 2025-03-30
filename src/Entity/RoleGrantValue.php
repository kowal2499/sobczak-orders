<?php

namespace App\Entity;

use App\ValueObject\Authorization\GrantValue;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: "role_grant_value",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_role_grant_value", columns: ["role_id", "grant_id"])
])]
class RoleGrantValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;

    #[ORM\ManyToOne(targetEntity: Grant::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private Grant $grant;

    #[ORM\Column(type: 'grant_value', nullable: false)]
    private GrantValue $value;

    /**
     * @param Role $role
     * @param Grant $grant
     * @param GrantValue $value
     */
    public function __construct(Role $role, Grant $grant, GrantValue $value)
    {
        $this->role = $role;
        $this->grant = $grant;
        $this->value = $value;
    }


}