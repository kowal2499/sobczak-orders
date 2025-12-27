<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\UpdateFactorRatioCommand;
use App\Module\Production\Repository\FactorRepository;

class UpdateFactorRatioCommandHandler
{

    public function __construct(
        private readonly FactorRepository $factorRepository,
    ) {
    }

    public function __invoke(updateFactorRatioCommand $command): void
    {
        $dto = $command->getRatioDTO();
        if (!$dto->getId()) {
            throw new \RuntimeException('RatioDTO must have an ID');
        }

        $factor = $this->factorRepository->find($dto->getId());
        if (!$factor) {
            throw new \InvalidArgumentException('Factor not found');
        }

        $factor->setFactorValue($dto->getValue());
        $factor->setDescription($dto->getDescription());
        $factor->setDepartmentSlug($dto->getDepartmentSlug());
        $this->factorRepository->save($factor);
    }
}