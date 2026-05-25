<?php

namespace App\Module\Agreement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\Agreement\Command\LogProductionActivityCommand;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Production\ValueObject\ProductionTaskStatus;
use App\Repository\ProductionRepository;
use App\System\CommandBus;

class LogProductionActivityCommandHandler
{
    private const NO_STATUS = '—';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ProductionRepository $productionRepository,
    ) {
    }

    public function __invoke(LogProductionActivityCommand $command): void
    {
        $production = $this->productionRepository->find($command->productionId);
        if ($production === null) {
            return;
        }

        $line = $production->getAgreementLine();
        if ($line === null) {
            return;
        }

        $department = DepartmentEnum::tryFrom((string) $production->getDepartmentSlug());
        $status = ProductionTaskStatus::tryFrom((int) $production->getStatus());
        $oldStatus = $command->oldStatusId !== null
            ? ProductionTaskStatus::tryFrom($command->oldStatusId)
            : null;

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.' . $command->type->value,
            type: $command->type->value,
            contextData: [
                'id' => (string) $line->getId(),
                'agreementId' => (string) $line->getAgreement()->getId(),
            ],
            contentParams: [
                'departmentName' => $department?->getName() ?? (string) $production->getDepartmentSlug(),
                'oldStatusName' => $oldStatus?->getName() ?? self::NO_STATUS,
                'newStatusName' => $status?->getName() ?? (string) $production->getStatus(),
            ],
        ));
    }
}
