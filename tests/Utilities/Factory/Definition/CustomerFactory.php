<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\Customer;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class CustomerFactory implements FactoryDefinitionInterface
{
    public static function supports(): string
    {
        return Customer::class;
    }

    public function defaultProperties(Generator $faker): array
    {
        return [
            'createDate' => new \DateTime(),
            'updateDate' => new \DateTime(),
            'name' => $faker->company,
            'firstName' => $faker->firstNameMale,
            'lastName' => $faker->lastName,
            'email' => $faker->email,
            'phone' => $faker->phoneNumber,
            'streetNumber' => $faker->numberBetween(0, 200),
            'street' => $faker->streetName,
            'apartmentNumber' => $faker->randomElement([null, $faker->numberBetween(0, 33)]),
            'city' => $faker->city,
            'postalCode' => $faker->postcode,
            'country' => $faker->countryCode
        ];
    }
}