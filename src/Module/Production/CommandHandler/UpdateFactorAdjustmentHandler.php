<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\UpdateFactorAdjustment;
use App\Module\Production\Repository\Interface\FactorAdjustmentRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UpdateFactorAdjustmentHandler
{
    public function __construct(
        private readonly FactorAdjustmentRepositoryInterface $factorAdjustRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function __invoke(UpdateFactorAdjustment $command): void
    {
        if (!$this->authorizationChecker->isGranted('production.factor_adjustment:update')) {
            throw new AccessDeniedException('Access Denied.');
        }
        $adjust = $this->factorAdjustRepository->find($command->getFactorAdjustId());
        if (!$adjust) {
            throw new InvalidArgumentException('FactorAdjust not found');
        }
        if ($adjust->getDescription() === $command->getDescription()
            && $adjust->getFactor() === $command->getFactor())
        {
            return;
        }
        $adjust->setDescription($command->getDescription());
        $adjust->setFactor($command->getFactor());

        $this->factorAdjustRepository->save($adjust);
    }
}
