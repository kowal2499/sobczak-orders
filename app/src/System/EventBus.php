<?php

namespace App\System;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class EventBus implements MessageBusInterface
{

    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        return $this->messageBus->dispatch($message, $stamps);
    }
}

