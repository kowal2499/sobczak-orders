<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Repository\FactorRepository;
use App\Repository\AgreementLineRepository;

class CreateFactorCommandHandler
{
    public function __construct(
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly FactorRepository $factorRepository,
    ) {
    }

    public function __invoke(CreateFactorCommand $command): void
    {
        $dto = $command->getRatioDTO();
        if ($dto->getId()) {
            throw new \InvalidArgumentException('New Factor cannot have an ID');
        }

        $agreementLine = $this->agreementLineRepository->find($command->getAgreementLineId());
        if (!$agreementLine) {
            throw new \InvalidArgumentException('Agreement Line not found');
        }

        $factor = new Factor();
        $factor->setAgreementLine($agreementLine);
        $factor->setSource($dto->getFactorSource());
        $factor->setDepartmentSlug($dto->getDepartmentSlug());
        $factor->setDescription($dto->getDescription());
        $factor->setFactorValue($dto->getValue());

        $this->factorRepository->save($factor);
    }
}
