<?php

namespace App\Module\Agreement\EventHandler;

use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Agreement\Event\AgreementLineWasUpdatedEvent;
use App\System\CommandBus;

class UpdateAgreementLineRMOnUpdateHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(AgreementLineWasUpdatedEvent $event): void
    {
        $this->commandBus->dispatch(new UpdateAgreementLineRM($event->getAgreementLineId()));
    }
}
