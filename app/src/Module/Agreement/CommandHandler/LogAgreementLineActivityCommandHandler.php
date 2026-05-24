<?php

namespace App\Module\Agreement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\Agreement\Command\LogAgreementLineActivityCommand;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;

class LogAgreementLineActivityCommandHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly AgreementLineRepository $agreementLineRepository,
    ) {
    }

    public function __invoke(LogAgreementLineActivityCommand $command): void
    {
        $line = $this->agreementLineRepository->find($command->agreementLineId);
        if ($line === null) {
            return;
        }

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.' . $command->type->value,
            type: $command->type->value,
            contextData: [
                'id' => (string) $line->getId(),
                'agreementId' => (string) $line->getAgreement()->getId(),
            ],
            contentParams: [
                'productName' => $line->getProduct()?->getName() ?? '',
            ],
        ));
    }
}
