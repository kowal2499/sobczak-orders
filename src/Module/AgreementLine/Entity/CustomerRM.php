<?php

namespace App\Module\AgreementLine\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class CustomerRM
{
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $firstName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lastName;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $phone;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email;

    #[ORM\Embedded(class: AddressRM::class, columnPrefix: 'address_')]
    private ?AddressRM $address;

    public function __construct(
        int $id,
        string $name,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?string $email = null,
        ?AddressRM $address = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->email = $email;
        $this->address = $address;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getAddress(): ?AddressRM
    {
        return $this->address;
    }
}