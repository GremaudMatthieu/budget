<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface DomainEvent
{
    public const string DEFAULT_REQUEST_ID = '4f3539bf-f986-4117-9236-203b47dc3955';

    public function toArray(): array;

    public static function fromArray(array $data): self;
}
