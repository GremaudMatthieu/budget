<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Traits;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\AbstractUserDomainEventInterface;

trait UserDomainEventsCapabilityTrait
{
    private array $events = [];

    protected function raiseDomainEvent(AbstractUserDomainEventInterface $event): void
    {
        $this->{sprintf('apply%s', new \ReflectionClass($event)->getShortName())}($event);
        $this->events[] = $event;
    }

    public function raisedDomainEvents(): array
    {
        return $this->events;
    }

    public function clearRaisedDomainEvents(): array
    {
        return $this->events = [];
    }
}
