<?php

namespace App\Module\Production\CommandHandler;

use App\Module\Production\Command\DeleteFactorAdjust;
use App\Module\Production\Repository\Interface\FactorAdjustRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DeleteFactorAdjustHandler
{
    public function __construct(
        private readonly FactorAdjustRepositoryInterface $factorAdjustRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(DeleteFactorAdjust $command): void
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
