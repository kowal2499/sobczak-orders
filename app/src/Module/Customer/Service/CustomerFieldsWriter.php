<?php

namespace App\Module\Customer\Service;

use App\Entity\Customer;
use App\Module\Customer\Command\CustomerData;

class CustomerFieldsWriter
{
    public function write(Customer $customer, CustomerData $data): void
    {
        $customer->setName($data->name);
        $customer->setFirstName($data->firstName);
        $customer->setLastName($data->lastName);
        $customer->setStreet($data->street);
        $customer->setStreetNumber($data->streetNumber);
        $customer->setApartmentNumber($data->apartmentNumber);
        $customer->setPostalCode($data->postalCode);
        $customer->setCity($data->city);
        $customer->setCountry($data->country);
        $customer->setPhone($data->phone);
        $customer->setEmail($data->email);
    }
}
