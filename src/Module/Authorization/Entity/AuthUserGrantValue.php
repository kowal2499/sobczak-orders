<?php

namespace App\Module\Authorization\Entity;

use App\Entity\User;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthUserGrantValueRepository::class)]
#[ORM\Table(
    name: "auth_user_grant_value",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_user_grant_value", columns: ["user_id", "grant_id", "grant_option_slug"])
])]
class AuthUserGrantValue extends AuthAbstractGrantValue
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    public function __construct(User $user, AuthGrant $grant, ?string $grantOptionSlug = null)
    {
        parent::__construct($grant, $grantOptionSlug);
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}