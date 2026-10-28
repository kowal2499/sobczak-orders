<?php

namespace App\Module\Customer\Command;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Fields shared by the create and update commands; validated through #[Assert\Valid] on them.
 */
class CustomerData
{
    public function __construct(
        #[Assert\NotBlank(message: 'Pole nie może być puste')]
        #[Assert\Length(max: 255)]
        public readonly string $name,
        #[Assert\Length(max: 255)]
        public readonly ?string $firstName = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $lastName = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $street = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $streetNumber = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $apartmentNumber = null,
        #[Assert\Length(max: 16)]
        public readonly ?string $postalCode = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $city = null,
        #[Assert\Country]
        public readonly ?string $country = null,
        #[Assert\Length(max: 255)]
        public readonly ?string $phone = null,
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public readonly ?string $email = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $text = static function (string $key) use ($data): ?string {
            $value = trim((string) ($data[$key] ?? ''));

            return $value !== '' ? $value : null;
        };

        return new self(
            (string) $text('name'),
            $text('firstName'),
            $text('lastName'),
            $text('street'),
            $text('streetNumber'),
            $text('apartmentNumber'),
            $text('postalCode'),
            $text('city'),
            $text('country'),
            $text('phone'),
            $text('email'),
        );
    }
}
