<?php

namespace App\Module\Agreement\EventHandler;

use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Agreement\Event\AgreementLineWasCreatedEvent;
use App\System\CommandBus;

class UpdateAgreementLineRMOnCreateHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(AgreementLineWasCreatedEvent $event): void
    {
        $this->commandBus->dispatch(new UpdateAgreementLineRM($event->getAgreementLineId()));
    }
}
