<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\AssignFactorAdjustments;
use App\Module\Production\Command\CreateFactorAdjustment;
use App\Module\Production\Command\UpdateFactorAdjustment;
use App\Module\Production\Command\DeleteFactorAdjustment;
use App\Module\Production\DTO\FactorAdjustmentDTO;
use App\Module\Production\Entity\FactorAdjustment;
use App\Module\Production\Repository\Interface\FactorAdjustmentRepositoryInterface;
use App\Repository\ProductionRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class AssignFactorAdjustmentsHandler
{
    public function __construct(
        private readonly FactorAdjustmentRepositoryInterface $factorAdjustmentRepository,
        private readonly ProductionRepository $productionRepository,
        private readonly CommandBus $commandBus,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(AssignFactorAdjustments $command): void
    {
        $production = $this->productionRepository->find($command->getProductionId());
        if (!$production) {
            throw new InvalidArgumentException('Production not found');
        }

        // Load existing adjustments for the production
        /** @var FactorAdjustment[] $existing */
        $existing = $this->factorAdjustmentRepository->findBy(['production' => $production]);
        $existingById = [];
        foreach ($existing as $adj) {
            $existingById[$adj->getId()] = $adj;
        }

        // Start DB transaction for the whole operation
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            // Collect ids from DTOs to determine which to keep
            $dtoIds = [];
            /** @var FactorAdjustmentDTO $dto */
            foreach ($command->getFactorAdjustments() as $dto) {
                $id = $dto->getId();
                if ($id !== null) {
                    $dtoIds[] = $id;
                    if (isset($existingById[$id])) {
                        $this->commandBus->dispatch(new UpdateFactorAdjustment($id, $dto->getDescription(), $dto->getFactor()));
                    } else {
                        throw new InvalidArgumentException('FactorAdjust not found');
                    }
                } else {
                    $this->commandBus->dispatch(new CreateFactorAdjustment($production->getId(), $dto->getDescription(), $dto->getFactor()));
                }
            }

            // Delete adjustments not present in DTO list
            foreach ($existing as $adj) {
                if (!in_array($adj->getId(), $dtoIds, true)) {
                    $this->commandBus->dispatch(new DeleteFactorAdjustment($adj->getId()));
                }
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

}