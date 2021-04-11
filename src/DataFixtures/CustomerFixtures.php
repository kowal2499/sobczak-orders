<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends BaseFixture
{
    protected function loadData(ObjectManager $manager)
    {

        $this->createMany(Customer::class, 100, function(Customer $customer, $count) {
            $customer
                ->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setName($this->faker->company())
                ->setCity($this->faker->city())
                ->setCountry('PL')
                ->setStreet($this->faker->streetName())
                ->setStreetNumber($this->faker->numberBetween(1, 200))
                ->setApartmentNumber($this->faker->numberBetween(1, 200))
                ->setPostalCode($this->faker->postcode())
                ->setPhone($this->faker->phoneNumber())
                ->setEmail($this->faker->email())

                ->setCreateDate($this->faker->dateTimeBetween('-123 days', '-1 days'))
                ->setUpdateDate(new \DateTime())
            ;

        });

        $manager->flush();

    }
}
