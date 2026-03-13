<?php

namespace App\Module\Agreement\EventHandler;

use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Agreement\Event\AgreementLineWasDeletedEvent;
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
