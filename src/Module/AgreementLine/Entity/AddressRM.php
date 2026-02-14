<?php

namespace App\Module\AgreementLine\Entity;

class AddressRM
{
    private ?string $street;
    private ?string $streetNumber;
    private ?string $apartmentNumber;
    private ?string $postalCode;
    private ?string $city;
    private ?string $country;

    public function __construct(
        ?string $street = null,
        ?string $streetNumber = null,
        ?string $apartmentNumber = null,
        ?string $postalCode = null,
        ?string $city = null,
        ?string $country = null,
    ) {
        $this->street = $street;
        $this->streetNumber = $streetNumber;
        $this->apartmentNumber = $apartmentNumber;
        $this->postalCode = $postalCode;
        $this->city = $city;
        $this->country = $country;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function getApartmentNumber(): ?string
    {
        return $this->apartmentNumber;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function getFullAddress(): ?string
    {
        $parts = array_filter([
            trim(($this->street ?? '') . ' ' . ($this->streetNumber ?? '') . ($this->apartmentNumber ? '/' . $this->apartmentNumber : '')),
            $this->postalCode ? $this->postalCode . ' ' . ($this->city ?? '') : $this->city,
            $this->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'streetNumber' => $this->streetNumber,
            'apartmentNumber' => $this->apartmentNumber,
            'postalCode' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            street: $data['street'] ?? null,
            streetNumber: $data['streetNumber'] ?? null,
            apartmentNumber: $data['apartmentNumber'] ?? null,
            postalCode: $data['postalCode'] ?? null,
            city: $data['city'] ?? null,
            country: $data['country'] ?? null,
        );
    }
}