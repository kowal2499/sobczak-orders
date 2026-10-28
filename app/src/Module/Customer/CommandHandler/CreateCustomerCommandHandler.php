<?php

namespace App\Module\Customer\CommandHandler;

use App\Entity\Customer;
use App\Module\Customer\Command\CreateCustomerCommand;
use App\Module\Customer\Service\CustomerFieldsWriter;
use Doctrine\ORM\EntityManagerInterface;

class CreateCustomerCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomerFieldsWriter $fieldsWriter,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $customer = new Customer();
        $this->fieldsWriter->write($customer, $command->data);
        $customer->setCreateDate(new \DateTime());
        $customer->setUpdateDate(new \DateTime());

        $this->em->persist($customer);
        $this->em->flush();
    }
}
