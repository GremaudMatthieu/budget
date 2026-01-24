<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Ports;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEvent;

interface EventEncryptor
{
    public function encrypt(DomainEvent $event, string $userId): DomainEvent;

    public function decrypt(DomainEvent $event, string $userId): DomainEvent;
}
