<?php

namespace App\Module\Agreement\EventHandler;

use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Task\Event\TaskWasCreatedEvent;
use App\System\CommandBus;

class UpdateAgreementLineRMOnTaskCreatedHandler
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(TaskWasCreatedEvent $event): void
    {
        $this->commandBus->dispatch(new UpdateAgreementLineRM($event->getAgreementLineId()));
    }
}
