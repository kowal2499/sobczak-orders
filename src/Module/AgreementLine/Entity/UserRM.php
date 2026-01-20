<?php

namespace App\Module\AgreementLine\Entity;

class UserRM
{
    private ?int $id;
    private ?string $firstName;
    private ?string $lastName;
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
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            firstName: $data['firstName'] ?? null,
            lastName: $data['lastName'] ?? null,
            email: $data['email'] ?? null,
        );
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}