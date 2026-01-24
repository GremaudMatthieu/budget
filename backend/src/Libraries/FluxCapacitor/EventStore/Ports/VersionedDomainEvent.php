<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface VersionedDomainEvent extends DomainEvent
{
    public function getVersion(): int;

    public function getEventType(): string;
}
