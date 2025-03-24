<?php

namespace App\Entity;

use App\ValueObject\Authorization\GrantValue;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: "user_grant_value",
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: "unique_user_grant", columns: ["user_id", "grant_id"])
])]
class UserGrantValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Grant::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private Grant $grant;

    #[ORM\Column(type: 'grant_value', nullable: false)]
    private GrantValue $value;

    /**
     * @param User $user
     * @param Grant $grant
     * @param GrantValue $value
     */
    public function __construct(User $user, Grant $grant, GrantValue $value)
    {
        $this->user = $user;
        $this->grant = $grant;
        $this->value = $value;
    }


}