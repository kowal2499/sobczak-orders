<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\CreateFactorAdjustment;
use App\Module\Production\Entity\FactorAdjustment;
use App\Module\Production\Repository\Interface\FactorAdjustmentRepositoryInterface;
use App\Repository\ProductionRepository;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @deprecated  */
class CreateFactorAdjustmentHandler
{

    public function __construct(
        private readonly FactorAdjustmentRepositoryInterface $factorAdjustRepository,
        private readonly ProductionRepository $productionRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(CreateFactorAdjustment $command): void
    {
        if (!$this->authorizationChecker->isGranted('production.factor_adjustment_bonus:create')) {
            throw new AccessDeniedException('Access Denied.');
        }

        $production = $this->productionRepository->find($command->getProductionId());
        if (!$production) {
            throw new InvalidArgumentException('Production not found');
        }
        $adjust = new FactorAdjustment();
        $adjust->setProduction($production);
        $adjust->setDescription($command->getDescription());
        $adjust->setFactor($command->getFactor());

        $this->factorAdjustRepository->save($adjust);
    }
}