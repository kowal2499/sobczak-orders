<?php

namespace App\Module\Production\Service;

use App\Entity\AgreementLine;
use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\Command\DeleteFactorCommand;
use App\Module\Production\Command\SetAgreementLineFactorCommand;
use App\Module\Production\Command\UpdateFactorCommand;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Repository\FactorRepository;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;

class FactorWriteService
{
    public function __construct(
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly FactorRepository $factorRepository,
        private readonly CommandBus $commandBus,
    ) {
    }

    /**
     * @param int $agreementLineId
     * @param FactorRatioDTO[] $factors
     * @return void
     */
    public function store(int $agreementLineId, array $factors): void
    {
        $agreementLine = $this->agreementLineRepository->find($agreementLineId);
        if (!$agreementLine) {
            throw new \InvalidArgumentException("Agreement line with ID $agreementLineId not found.");
        }

        $this->processAgreementLineFactor(
            $agreementLine,
            array_filter($factors, fn (FactorRatioDTO $dto) => $dto->getFactorSource() === FactorSource::AGREEMENT_LINE)[0] ?? null
        );

        $this->processFactorData(
            $agreementLine,
            array_filter($factors, fn (FactorRatioDTO $dto) => $dto->getFactorSource() === FactorSource::FACTOR_ADJUSTMENT_RATIO),
            FactorSource::FACTOR_ADJUSTMENT_RATIO
        );

        $this->processFactorData(
            $agreementLine,
            array_filter($factors, fn (FactorRatioDTO $dto) => $dto->getFactorSource() === FactorSource::FACTOR_ADJUSTMENT_BONUS),
            FactorSource::FACTOR_ADJUSTMENT_BONUS
        );
    }

    protected function processAgreementLineFactor(AgreementLine $agreementLine, ?FactorRatioDTO $dto): void
    {
        if (!$dto) {
            return;
        }
        $this->commandBus->dispatch(new SetAgreementLineFactorCommand(
            $agreementLine->getId(),
            $dto->getValue()
        ));
    }

    /**
     * @param AgreementLine $agreementLine
     * @param FactorRatioDTO[] $factorDataList
     * @param FactorSource $source
     * @return void
     */
    protected function processFactorData(AgreementLine $agreementLine, array $factorDataList, FactorSource $source): void
    {
        $existingFactors = array_map(fn (Factor $factor) => $factor->getId(),
            $this->factorRepository->findBy(['agreementLine' => $agreementLine, 'source' => $source])
        );
        $processedFactorIds = [];

        foreach ($factorDataList as $factorData) {
            if ($factorData->getId()) {
                $command = new UpdateFactorCommand($agreementLine->getId(), $factorData);
                $processedFactorIds[] = $factorData->getId();
            } else {
                $command = new CreateFactorCommand($agreementLine->getId(), $factorData);
            }
            $this->commandBus->dispatch($command);
        }
        $factorsToDelete = array_diff($existingFactors, $processedFactorIds);
        foreach ($factorsToDelete as $factorId) {
            $this->commandBus->dispatch(new DeleteFactorCommand($factorId));
        }
    }
}