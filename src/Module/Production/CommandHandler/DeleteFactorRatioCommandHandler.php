<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\DeleteFactorRatioCommand;
use App\Module\Production\Repository\FactorRepository;

class DeleteFactorRatioCommandHandler
{

    public function __construct(
        private readonly FactorRepository $factorRepository,
    ) {
    }

    public function __invoke(DeleteFactorRatioCommand $command): void
    {

        $factor = $this->factorRepository->find($command->getFactorRatioId());
        if (!$factor) {
            throw new \InvalidArgumentException('Factor not found');
        }

        $this->factorRepository->delete($factor);
    }
}