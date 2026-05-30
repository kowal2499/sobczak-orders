<?php

namespace App\Module\Agreement\Command;

use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use Symfony\Component\Validator\Constraints as Assert;

final class LogProductionDateChangedCommand
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $productionId,
        public readonly AgreementActivityLogType $type,
        public readonly ?string $oldDate,
        public readonly ?string $newDate,
    ) {
    }
}
