<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\ProjectionManager\Ports;

interface ProjectionRegistryInterface
{
    public function getAllProjections(): array;

    public function getTableForProjection(string $projectionClass): string;

    public function getEventTypesForProjection(string $projectionClass): array;

    public function getContextFromProjectionClass(string $projectionClass): ?string;
}
