<?php

namespace App\Module\Agreement\Command;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAgreementCommand
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        public readonly int $customerId,
        #[Assert\NotBlank]
        public readonly string $orderNumber,
        #[Assert\NotBlank]
        #[Assert\Type('array')]
        #[Assert\Count(min: 1)]
        public readonly array $products,
        #[Assert\NotNull]
        public readonly int $userId,
        #[Assert\Type('array')]
        public readonly array $attachments = [],
    ) {
    }
}
