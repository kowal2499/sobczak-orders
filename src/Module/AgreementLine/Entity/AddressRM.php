<?php

namespace App\Module\AgreementLine\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class AddressRM
{
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $street;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    private ?string $streetNumber;

    #[ORM\Column(type: 'string', length: 8, nullable: true)]
    private ?string $apartmentNumber;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $postalCode;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    private ?string $city;

    #[ORM\Column(type: 'string', length: 2, nullable: true)]
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
}