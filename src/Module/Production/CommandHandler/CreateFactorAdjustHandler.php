<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\CreateFactorAdjust;
use App\Module\Production\Entity\FactorAdjust;
use App\Module\Production\Repository\Interface\FactorAdjustRepositoryInterface;
use App\Repository\ProductionRepository;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CreateFactorAdjustHandler
{

    public function __construct(
        private readonly FactorAdjustRepositoryInterface $factorAdjustRepository,
        private readonly ProductionRepository $productionRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(CreateFactorAdjust $command): void
    {
        if (!$this->authorizationChecker->isGranted('production.factor_adjustment:create')) {
            throw new AccessDeniedException('Access Denied.');
        }

        $production = $this->productionRepository->find($command->getProductionId());
        if (!$production) {
            throw new InvalidArgumentException('Production not found');
        }
        $adjust = new FactorAdjust();
        $adjust->setProduction($production);
        $adjust->setDescription($command->getDescription());
        $adjust->setFactor($command->getFactor());

        $this->factorAdjustRepository->save($adjust);
    }
}