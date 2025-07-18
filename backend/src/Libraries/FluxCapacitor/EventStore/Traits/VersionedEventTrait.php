<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Traits;

trait VersionedEventTrait
{
    public function getVersion(): int
    {
        return static::VERSION;
    }

    public function getEventType(): string
    {
        return static::EVENT_TYPE;
    }
}
