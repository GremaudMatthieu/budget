<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Ports;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

interface EventEncryptorInterface
{
    public function encrypt(DomainEventInterface $event, string $userId): DomainEventInterface;

    public function decrypt(DomainEventInterface $event, string $userId): DomainEventInterface;
}
