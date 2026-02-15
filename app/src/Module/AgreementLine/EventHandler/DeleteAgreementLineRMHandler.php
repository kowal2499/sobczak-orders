<?php

namespace App\Module\AgreementLine\EventHandler;

use App\Module\AgreementLine\Command\UpdateAgreementLineRM;
use App\Module\AgreementLine\Event\AgreementLineWasDeletedEvent;
use App\Module\AgreementLine\Event\AgreementLineWasUpdatedEvent;
use App\System\CommandBus;

class DeleteAgreementLineRMHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(AgreementLineWasDeletedEvent $event): void
    {
        $this->commandBus->dispatch(new UpdateAgreementLineRM($event->getAgreementLineId()));
    }
}
