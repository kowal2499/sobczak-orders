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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $grantOptionSlug;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $value = true;

    public function __construct(AuthRole $role, AuthGrant $grant, ?string $grantOptionSlug = null)
    {
        $this->role = $role;
        $this->grant = $grant;
        $this->grantOptionSlug = $grantOptionSlug;
    }

    public function getGrant(): AuthGrant
    {
        return $this->grant;
    }

    public function getValue(): ?bool
    {
        return $this->value;
    }

    public function getRole(): AuthRole
    {
        return $this->role;
    }

    public function getGrantOptionSlug(): string
    {
        return $this->grantOptionSlug;
    }

    public function setValue(?bool $value): void
    {
        $this->value = $value;
    }
}