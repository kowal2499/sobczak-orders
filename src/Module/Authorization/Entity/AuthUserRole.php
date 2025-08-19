<?php

namespace App\Module\Authorization\Entity;

use App\Entity\User;
use App\Module\Authorization\Repository\AuthUserRoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthUserRoleRepository::class)]
#[ORM\Table(
    name: "auth_user_role",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_user_role", columns: ["user_id", "role_id"])
])]
class AuthUserRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: AuthRole::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private AuthRole $role;

    /**
     * @param User $user
     * @param AuthRole $role
     */
    public function __construct(User $user, AuthRole $role)
    {
        $this->user = $user;
        $this->role = $role;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setRole(AuthRole $role): AuthUserRole
    {
        $this->role = $role;
        return $this;
    }

    public function getRole(): AuthRole
    {
        return $this->role;
    }
}