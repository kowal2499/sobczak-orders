<?php

namespace App\Module\Authorization\Entity;

use App\Entity\User;
use App\Module\Authorization\ValueObject\GrantValue;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: "auth_user_grant_value",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_user_grant", columns: ["user_id", "grant_id"])
])]
class AuthUserGrantValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: AuthGrant::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private AuthGrant $grant;

    #[ORM\Column(type: 'grant_value', nullable: false)]
    private GrantValue $value;

    /**
     * @param User $user
     * @param AuthGrant $grant
     * @param GrantValue $value
     */
    public function __construct(User $user, AuthGrant $grant, GrantValue $value)
    {
        $this->user = $user;
        $this->grant = $grant;
        $this->value = $value;
    }


}