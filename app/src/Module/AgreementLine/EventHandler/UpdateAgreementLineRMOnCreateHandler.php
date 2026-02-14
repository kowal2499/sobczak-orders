<?php

namespace App\Module\AgreementLine\EventHandler;

use App\Module\AgreementLine\Command\UpdateAgreementLineRM;
use App\Module\AgreementLine\Event\AgreementLineWasCreatedEvent;
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
