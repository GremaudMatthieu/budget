<?php

namespace App\Libraries\FluxCapacitor\EventStore\Services;

use App\Libraries\FluxCapacitor\EventStore\Ports\EventBusInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class EventBus implements EventBusInterface
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    public function execute(array $events): void
    {
        foreach ($events as $event) {
            $this->messageBus->dispatch($event);
        }
    }
}
