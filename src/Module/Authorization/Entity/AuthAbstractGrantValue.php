<?php

namespace App\Module\Authorization\Entity;
use App\Module\Authorization\ValueObject\GrantVO;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AuthAbstractGrantValue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    #[ORM\ManyToOne(targetEntity: AuthGrant::class)]
    #[ORM\JoinColumn(nullable: false)]
    protected AuthGrant $grant;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $grantOptionSlug;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $value = true;

    public function __construct(AuthGrant $grant, ?string $grantOptionSlug = null)
    {
        $this->grant = $grant;
        $this->grantOptionSlug = $grantOptionSlug;
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

    public function getGrantVO(): GrantVO
    {
        return GrantVO::m(implode(':', array_filter([$this->grant->getSlug(), $this->grantOptionSlug])));
    }
}