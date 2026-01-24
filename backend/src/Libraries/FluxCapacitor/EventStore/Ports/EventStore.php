<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface EventStore
{
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): AggregateRoot;

    public function loadByDomainEvents(string $uuid, array $domainEventClasses, ?\DateTimeImmutable $desiredDateTime = null): \Generator;

    public function save(AggregateRoot $aggregate): void;

    public function saveMultiAggregate(array $aggregates): void;

    public function trackAggregates(array $aggregates): void;

    public function trackAggregate(AggregateRoot $aggregate): void;
}
