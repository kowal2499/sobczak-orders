<?php

namespace App\Module\Authorization\Entity;

use App\Entity\User;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthUserGrantValueRepository::class)]
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $grantOptionSlug;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $value = true;

    public function __construct(User $user, AuthGrant $grant, ?string $grantOptionSlug = null)
    {
        $this->user = $user;
        $this->grant = $grant;
        $this->grantOptionSlug = $grantOptionSlug;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getGrant(): AuthGrant
    {
        return $this->grant;
    }

    public function getGrantOptionSlug(): ?string
    {
        return $this->grantOptionSlug;
    }

    public function getValue(): ?bool
    {
        return $this->value;
    }

    public function setValue(?bool $value): void
    {
        $this->value = $value;
    }


}