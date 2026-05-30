<?php

namespace App\Module\Agreement\Command;

use Symfony\Component\Validator\Constraints as Assert;

final class LogAgreementUpdatedCommand
{
    /**
     * @param array<int, array<string, mixed>> $changes list of change descriptors
     *                                                   (see AgreementUpdatedContent.vue for the contract)
     */
    public function __construct(
        #[Assert\Positive]
        public readonly int $agreementId,
        #[Assert\Type('array')]
        #[Assert\Count(min: 1)]
        public readonly array $changes,
    ) {
    }
}
