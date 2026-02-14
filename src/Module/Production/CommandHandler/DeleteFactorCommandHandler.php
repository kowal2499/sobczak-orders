<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\DeleteFactorCommand;
use App\Module\Production\Repository\FactorRepository;

class DeleteFactorCommandHandler
{

    public function __construct(
        private readonly FactorRepository $factorRepository,
    ) {
    }

    public function __invoke(DeleteFactorCommand $command): void
    {

        $factor = $this->factorRepository->find($command->getFactorId());
        if (!$factor) {
            throw new \InvalidArgumentException('Factor not found');
        }

        $this->factorRepository->delete($factor);
    }
}