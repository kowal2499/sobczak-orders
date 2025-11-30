<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\UpdateFactorAdjust;
use App\Module\Production\Repository\Interface\FactorAdjustRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UpdateFactorAdjustHandler
{
    public function __construct(
        private readonly FactorAdjustRepositoryInterface $factorAdjustRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function __invoke(UpdateFactorAdjust $command): void
    {
        if (!$this->authorizationChecker->isGranted('production.factor_adjustment:update')) {
            throw new AccessDeniedException('Access Denied.');
        }
        $adjust = $this->factorAdjustRepository->find($command->getFactorAdjustId());
        if (!$adjust) {
            throw new InvalidArgumentException('FactorAdjust not found');
        }
        $adjust->setDescription($command->getDescription());
        $adjust->setFactor($command->getFactor());

        $this->factorAdjustRepository->save($adjust);
    }
}
