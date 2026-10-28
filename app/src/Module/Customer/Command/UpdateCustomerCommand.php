<?php

namespace App\Module\Customer\Command;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCustomerCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $customerId,
        #[Assert\Valid]
        public readonly CustomerData $data,
    ) {
    }
}
