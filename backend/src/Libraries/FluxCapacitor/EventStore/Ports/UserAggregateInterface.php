<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface UserAggregateInterface
{
    public static function empty(): self;

    public function setAggregateVersion(int $aggregateVersion): self;
}
