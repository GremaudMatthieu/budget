<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface AggregateRootInterface
{
    public static function empty(): self;

    public function setAggregateVersion(int $aggregateVersion): self;

    public function getAggregateId(): string;

    public function clearKeys(): array;
}
