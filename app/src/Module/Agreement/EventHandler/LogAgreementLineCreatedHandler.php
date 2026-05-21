<?php

namespace App\Module\Agreement\EventHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Module\Agreement\Event\AgreementLineWasCreatedEvent;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;

class LogAgreementLineCreatedHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly AgreementLineRepository $agreementLineRepository,
    ) {
    }

    public function __invoke(AgreementLineWasCreatedEvent $event): void
    {
        $line = $this->agreementLineRepository->find($event->getAgreementLineId());
        if ($line === null) {
            return;
        }

        $this->commandBus->dispatch(new AddActivityLogCommand(
            message: 'activity_log.agreement_line.created',
            type: AgreementActivityLogType::AGREEMENT_LINE_CREATED->value,
            contextData: [
                'id' => (string) $line->getId(),
                'agreementId' => (string) $line->getAgreement()->getId(),
            ],
        ));
    }
}
