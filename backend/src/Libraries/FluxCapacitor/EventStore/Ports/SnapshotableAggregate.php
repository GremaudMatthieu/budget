<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface SnapshotableAggregate extends AggregateRoot
{
    public function createSnapshot(): array;

    public static function fromSnapshot(array $data, int $version): self;
}
