<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\AssignFactorAdjustments;

class AssignFactorAdjustmentsHandler
{
    public function __invoke(AssignFactorAdjustments $command)
    {
        throw new \RuntimeException('Implement me!');
    }

}