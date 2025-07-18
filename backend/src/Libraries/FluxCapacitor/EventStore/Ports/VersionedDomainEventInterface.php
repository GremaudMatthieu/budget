<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface VersionedDomainEventInterface extends DomainEventInterface
{
    public function getVersion(): int;
    public function getEventType(): string;
}