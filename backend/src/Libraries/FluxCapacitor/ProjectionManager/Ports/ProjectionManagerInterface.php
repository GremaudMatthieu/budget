<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\ProjectionManager\Ports;

interface ProjectionManagerInterface
{
    public function replayProjection(string $projectionClass, ?\DateTimeImmutable $fromDate = null, int $batchSize = 5000): int;

    public function resetProjection(string $projectionClass): void;

    public function getProjectionStatus(string $projectionClass): array;

    public function replayAllProjections(?\DateTimeImmutable $fromDate = null, int $batchSize = 5000): array;

    public function getAllProjections(): array;
}
