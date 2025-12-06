<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\DeleteFactorAdjustment;
use App\Module\Production\Repository\Interface\FactorAdjustmentRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DeleteFactorAdjustmentHandler
{
    public function __construct(
        private readonly FactorAdjustmentRepositoryInterface $factorAdjustRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(DeleteFactorAdjustment $command): void
    {
        if (!$this->authorizationChecker->isGranted('production.factor_adjustment:delete')) {
            throw new AccessDeniedException('Access Denied.');
        }

        $adjust = $this->factorAdjustRepository->find($command->getFactorAdjustId());
        if (!$adjust) {
            throw new InvalidArgumentException('FactorAdjust not found');
        }
        $this->factorAdjustRepository->delete($adjust);
    }
}
