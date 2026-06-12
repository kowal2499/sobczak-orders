<?php

namespace App\Module\Agreement\Command;

use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use Symfony\Component\Validator\Constraints as Assert;

final class LogAgreementLineActivityCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $agreementLineId,
        public readonly AgreementActivityLogType $type,
    ) {
    }
}
