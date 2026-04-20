<?php

namespace App\Module\Agreement\EventHandler;

use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Task\Event\TaskWasUpdatedEvent;
use App\System\CommandBus;

class UpdateAgreementLineRMOnTaskUpdatedHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(TaskWasUpdatedEvent $event): void
    {
        $this->commandBus->dispatch(new UpdateAgreementLineRM($event->getAgreementLineId()));
    }
}
