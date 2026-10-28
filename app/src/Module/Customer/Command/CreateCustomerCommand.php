<?php

namespace App\Module\Customer\Command;

use Symfony\Component\Validator\Constraints as Assert;

class CreateCustomerCommand
{
    public function __construct(
        #[Assert\Valid]
        public readonly CustomerData $data,
    ) {
    }
}
