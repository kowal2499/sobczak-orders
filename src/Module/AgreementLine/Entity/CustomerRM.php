<?php

namespace App\Module\AgreementLine\Entity;

class CustomerRM
{
    private int $id;
    private string $name;
    private ?string $firstName;
    private ?string $lastName;
    private ?string $phone;
    private ?string $email;
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

    public function getFullName(): ?string
    {
        if (!$this->firstName && !$this->lastName) {
            return null;
        }
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address?->toArray(),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            firstName: $data['firstName'] ?? null,
            lastName: $data['lastName'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            address: AddressRM::fromArray($data['address'] ?? [])
        );
    }
}