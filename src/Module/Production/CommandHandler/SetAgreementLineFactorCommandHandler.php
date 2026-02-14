<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\Command\SetAgreementLineFactorCommand;
use App\Module\Production\Command\UpdateFactorCommand;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\FactorSource;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;

class SetAgreementLineFactorCommandHandler
{
    public function __construct(
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(SetAgreementLineFactorCommand $command): void
    {
        $agreementLine = $this->agreementLineRepository->find($command->getAgreementLineId());
        if (!$agreementLine) {
            throw new \InvalidArgumentException('Agreement Line not found');
        }

        // save factor on agreement line
        if ($agreementLine->getFactor() !== $command->getFactorValue()) {
            $agreementLine->setFactor($command->getFactorValue());
            $this->agreementLineRepository->save($agreementLine, false);
        }

        // handle factor in collection
        $collectionFactor = $agreementLine->getFactorFromCollection();

        // create if not exists
        if (!$collectionFactor) {
            $this->commandBus->dispatch(new CreateFactorCommand(
                $agreementLine->getId(),
                new FactorRatioDTO(
                    FactorSource::AGREEMENT_LINE,
                    $command->getFactorValue(),
                    null,
                    null,
                    null,
                )
            ));
            return;
        }

        // update if value changed
        if ($collectionFactor->getFactorValue() !== $command->getFactorValue()) {
            $this->commandBus->dispatch(new UpdateFactorCommand(
                $agreementLine->getId(),
                new FactorRatioDTO(
                    FactorSource::AGREEMENT_LINE,
                    $command->getFactorValue(),
                    $collectionFactor->getId(),
                    null,
                    null,
                )
            ));
        }
    }
}