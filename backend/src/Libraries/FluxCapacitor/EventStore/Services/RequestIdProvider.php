<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Services;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEvent;

class RequestIdProvider
{
    public string $requestId = DomainEvent::DEFAULT_REQUEST_ID;
}
