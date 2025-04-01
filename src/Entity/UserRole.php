<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: "user_role",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_user_role", columns: ["user_id", "role_id"])
])]
class UserRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;

    /**
     * @param User $user
     * @param Role $role
     */
    public function __construct(User $user, Role $role)
    {
        $this->user = $user;
        $this->role = $role;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setRole(Role $role): UserRole
    {
        $this->role = $role;
        return $this;
    }
}