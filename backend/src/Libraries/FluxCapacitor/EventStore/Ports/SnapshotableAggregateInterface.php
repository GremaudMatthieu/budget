<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface SnapshotableAggregateInterface extends AggregateRootInterface
{
    public function createSnapshot(): array;
    public static function fromSnapshot(array $data, int $version): self;
}