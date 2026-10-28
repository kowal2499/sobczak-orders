<?php

namespace App\Module\Customer\CommandHandler;

use App\Module\Customer\Command\UpdateCustomerCommand;
use App\Module\Customer\Service\CustomerFieldsWriter;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpdateCustomerCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomerRepository $customerRepository,
        private readonly CustomerFieldsWriter $fieldsWriter,
    ) {
    }

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->customerRepository->find($command->customerId);
        if ($customer === null) {
            throw new \InvalidArgumentException('Klient nie istnieje');
        }

        $this->fieldsWriter->write($customer, $command->data);
        $customer->setUpdateDate(new \DateTime());

        $this->em->flush();
    }
}
