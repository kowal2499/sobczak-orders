<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\Customer;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class CustomerFactory implements FactoryDefinitionInterface
{
    public function define(Generator $faker, callable $callback = null)
    {
        return (new Customer())
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setName($faker->company)
            ->setFirstName($faker->firstNameMale)
            ->setLastName($faker->lastName)
            ->setEmail($faker->email)
            ->setPhone($faker->phoneNumber)
            ->setStreetNumber($faker->numberBetween(0, 200))
            ->setStreet($faker->streetName)
            ->setApartmentNumber($faker->randomElement([null, $faker->numberBetween(0, 30)]))
            ->setCity($faker->city)
            ->setPostalCode($faker->postcode)
            ->setCountry($faker->countryCode);
    }

    public static function supports(): string
    {
        return Customer::class;
    }
}