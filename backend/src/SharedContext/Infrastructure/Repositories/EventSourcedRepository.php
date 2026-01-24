<?php

namespace App\SharedContext\Infrastructure\Repositories;

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRoot;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStore;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository as EventSourcedRepositoryInterface;

final readonly class EventSourcedRepository implements EventSourcedRepositoryInterface
{
    public function __construct(private EventStore $eventStore)
    {
    }

    #[\Override]
    public function get(string $aggregateId, ?\DateTimeImmutable $desiredDateTime = null): AggregateRoot
    {
        return $this->eventStore->load($aggregateId, $desiredDateTime);
    }

    public function trackAggregate(AggregateRoot $aggregate): void
    {
        $this->eventStore->trackAggregate($aggregate);
    }

    public function trackAggregates(array $aggregates): void
    {
        $this->eventStore->trackAggregates($aggregates);
    }

    #[\Override]
    public function getByDomainEvents(
        string $aggregateId,
        array $domainEventClasses,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): \Generator {
        return $this->eventStore->loadByDomainEvents($aggregateId, $domainEventClasses, $desiredDateTime);
    }

    #[\Override]
    public function save(AggregateRoot $aggregate): void
    {
        $this->eventStore->save($aggregate);
    }

    #[\Override]
    public function saveMultiAggregate(array $aggregates): void
    {
        $this->eventStore->saveMultiAggregate($aggregates);
    }
}
