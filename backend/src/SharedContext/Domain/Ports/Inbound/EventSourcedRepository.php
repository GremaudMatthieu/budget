<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Inbound;

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRoot;

interface EventSourcedRepository
{
    public function get(
        string $aggregateId,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): AggregateRoot;

    public function getByDomainEvents(
        string $aggregateId,
        array $domainEventClasses,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): \Generator;

    public function save(AggregateRoot $aggregate): void;

    public function trackAggregate(AggregateRoot $aggregate): void;

    public function trackAggregates(array $aggregates): void;

    public function saveMultiAggregate(array $aggregates): void;
}
