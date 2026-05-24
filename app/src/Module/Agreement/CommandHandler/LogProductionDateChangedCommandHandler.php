<?php

namespace App\Module\Agreement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\Agreement\Command\LogProductionDateChangedCommand;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Repository\ProductionRepository;
use App\System\CommandBus;

class LogProductionDateChangedCommandHandler
{
    private const NO_DATE = '—';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ProductionRepository $productionRepository,
    ) {
    }

    public function __invoke(LogProductionDateChangedCommand $command): void
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

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.' . $command->type->value,
            type: $command->type->value,
            contextData: [
                'id' => (string) $line->getId(),
                'agreementId' => (string) $line->getAgreement()->getId(),
            ],
            contentParams: [
                'departmentName' => $department?->getName() ?? (string) $production->getDepartmentSlug(),
                'oldDate' => $command->oldDate ?? self::NO_DATE,
                'newDate' => $command->newDate ?? self::NO_DATE,
            ],
        ));
    }
}
