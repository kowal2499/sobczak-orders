<?php

namespace App\Module\AgreementLine\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class UserRM
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $firstName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lastName;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $email;

    public function __construct(
        ?int $id = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $email = null
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}