<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\ProjectionManager\Services;

use App\Libraries\FluxCapacitor\ProjectionManager\Ports\ProjectionRegistryInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;

final readonly class ProjectionRegistry implements ProjectionRegistryInterface
{
    public function __construct(
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function getAllProjections(): array
    {
        return array_keys($this->eventClassMap->getProjections());
    }

    public function getTableForProjection(string $projectionClass): string
    {
        $projections = $this->eventClassMap->getProjections();

        if (!isset($projections[$projectionClass])) {
            throw new \InvalidArgumentException("Projection not found in configuration: {$projectionClass}");
        }

        return $projections[$projectionClass]['table'];
    }

    public function getEventTypesForProjection(string $projectionClass): array
    {
        $projections = $this->eventClassMap->getProjections();

        if (!isset($projections[$projectionClass])) {
            throw new \InvalidArgumentException("Projection not found in configuration: {$projectionClass}");
        }

        $aggregate = $projections[$projectionClass]['aggregate'];

        return $this->getEventsByContext($aggregate);
    }

    public function getContextFromProjectionClass(string $projectionClass): ?string
    {
        $projections = $this->eventClassMap->getProjections();

        if (!isset($projections[$projectionClass])) {
            return null;
        }

        return $projections[$projectionClass]['aggregate'];
    }

    private function getEventsByContext(string $context): array
    {
        $events = [];
        $eventToAggregate = $this->eventClassMap->getEventToAggregateMap();

        foreach ($eventToAggregate as $eventName => $aggregateName) {
            if ($aggregateName === $context) {
                $events[] = $eventName;
            }
        }

        return $events;
    }
}
