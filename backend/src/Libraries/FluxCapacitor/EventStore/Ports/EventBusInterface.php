<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface EventBusInterface
{
    public function execute(array $events): void;
}
