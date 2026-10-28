<?php

namespace App\Module\Customer\DTO;

use App\Entity\Customer;

class CustomerDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $street,
        public readonly ?string $streetNumber,
        public readonly ?string $apartmentNumber,
        public readonly ?string $postalCode,
        public readonly ?string $city,
        public readonly ?string $country,
        public readonly ?string $phone,
        public readonly ?string $email,
    ) {
    }

    public static function fromEntity(Customer $customer): self
    {
        return new self(
            $customer->getId(),
            (string) $customer->getName(),
            $customer->getFirstName(),
            $customer->getLastName(),
            $customer->getStreet(),
            $customer->getStreetNumber(),
            $customer->getApartmentNumber(),
            $customer->getPostalCode(),
            $customer->getCity(),
            $customer->getCountry(),
            $customer->getPhone(),
            $customer->getEmail(),
        );
    }
}
