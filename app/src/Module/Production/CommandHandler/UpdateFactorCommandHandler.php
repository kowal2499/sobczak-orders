<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\UpdateFactorCommand;
use App\Module\Production\Repository\FactorRepository;

class UpdateFactorCommandHandler
{
    public function __construct(
        private readonly FactorRepository $factorRepository,
    ) {
    }

    public function __invoke(UpdateFactorCommand $command): void
    {
        $dto = $command->getRatioDTO();
        if (!$dto->getId()) {
            throw new \RuntimeException('FactorDTO must have an ID');
        }

        $factor = $this->factorRepository->find($dto->getId());
        if (!$factor) {
            throw new \InvalidArgumentException('Factor not found');
        }

        $factor->setFactorValue($dto->getValue());
        $factor->setDescription($dto->getDescription());
        $factor->setDepartmentSlug($dto->getDepartmentSlug());
        $factor->setSource($dto->getFactorSource());
        $this->factorRepository->save($factor);
    }
}
