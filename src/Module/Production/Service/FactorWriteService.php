<?php

namespace App\Module\Production\Service;

use App\Entity\AgreementLine;
use App\Module\Production\Command\CreateFactorRatioCommand;
use App\Module\Production\Command\DeleteFactorRatioCommand;
use App\Module\Production\Command\UpdateFactorRatioCommand;
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

        $this->processRatioData(
            $agreementLine,
            array_filter($factors, fn (FactorRatioDTO $dto) => $dto->getFactorSource() === FactorSource::FACTOR_ADJUSTMENT_RATIO)
        );

        $this->processBonusData(
            $agreementLine,
            array_filter($factors, fn (FactorRatioDTO $dto) => $dto->getFactorSource() === FactorSource::FACTOR_ADJUSTMENT_BONUS)
        );

    }

    /**
     * @param AgreementLine $agreementLine
     * @param FactorRatioDTO[] $ratioData
     * @return void
     */
    protected function processRatioData(AgreementLine $agreementLine, array $ratioData): void
    {
        $existingFactors = array_map(fn (Factor $factor) => $factor->getId(),
            $this->factorRepository->findBy(['agreementLine' => $agreementLine, 'source' => FactorSource::FACTOR_ADJUSTMENT_RATIO])
        );
        $processedFactorIds = [];

        foreach ($ratioData as $factorData) {
            if ($factorData->getId()) {
                $command = new UpdateFactorRatioCommand($agreementLine->getId(), $factorData);
                $processedFactorIds[] = $factorData->getId();
            } else {
                $command = new CreateFactorRatioCommand($agreementLine->getId(), $factorData);
            }
            $this->commandBus->dispatch($command);
        }
        $factorsToDelete = array_diff($existingFactors, $processedFactorIds);
        foreach ($factorsToDelete as $factorId) {
            $this->commandBus->dispatch(new DeleteFactorRatioCommand($factorId));
        }
    }

    protected function processBonusData(AgreementLine $agreementLine, array $ratioData): void
    {

    }
}