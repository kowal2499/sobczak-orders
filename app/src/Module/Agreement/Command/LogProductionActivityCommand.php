<?php

namespace App\Module\Agreement\Command;

use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use Symfony\Component\Validator\Constraints as Assert;

final class LogProductionActivityCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $productionId,
        public readonly AgreementActivityLogType $type,
    ) {
    }
}
