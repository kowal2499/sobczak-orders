<?php

namespace App\Module\Authorization\Entity;

use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\ValueObject\GrantType;
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
        $this->setValue($value);
    }

    public function getGrant(): AuthGrant
    {
        return $this->grant;
    }

    public function getValue(): GrantValue
    {
        return $this->value;
    }

    public function setValue(GrantValue $value): void
    {
        $rawValue = $value->getRawValue();
        if ($this->grant->getType() === GrantType::Select) {
            $supportedValues = [];
            foreach ($this->grant->getOptions() as $option) {
                $supportedValues[] = $option->getValue();
            }

            if (false === is_array($rawValue)) {
                throw new \RuntimeException("Only array values are accepted for Select type grants");
            }

            foreach ($rawValue as $item) {
                if (false === in_array($item, $supportedValues)) {
                    throw new \RuntimeException("Value '$item' not exists in options");
                }
            }
        } elseif ($this->grant->getType() === GrantType::Boolean) {
            if (false === is_bool($rawValue)) {
                throw new \RuntimeException("Only boolean values are accepted for Boolean type grants");
            }
        }

        $this->value = $value;
    }
}