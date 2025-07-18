<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface EventUpcastingServiceInterface
{
    public function upcastEvent(array $eventData): array;
}
