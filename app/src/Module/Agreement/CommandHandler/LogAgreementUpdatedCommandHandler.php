<?php

namespace App\Module\Agreement\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Module\Agreement\Command\LogAgreementUpdatedCommand;
use App\System\CommandBus;

class LogAgreementUpdatedCommandHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(LogAgreementUpdatedCommand $command): void
    {
        if ($command->changes === []) {
            return;
        }

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.agreement.updated',
            type: AgreementActivityLogType::AGREEMENT_UPDATED->value,
            contextData: [
                'agreementId' => (string) $command->agreementId,
            ],
            contentParams: [
                'changes' => $command->changes,
            ],
        ));
    }
}
