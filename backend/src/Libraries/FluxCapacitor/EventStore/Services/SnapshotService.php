<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Services;

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\SnapshotableAggregateInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use Doctrine\DBAL\Connection;

final readonly class SnapshotService
{
    public function __construct(
        private Connection $connection,
        private int $snapshotFrequency = 50,
    ) {
    }

    public function shouldCreateSnapshot(AggregateRootInterface $aggregate): bool
    {
        return 0 === $aggregate->aggregateVersion() % $this->snapshotFrequency;
    }

    public function saveSnapshot(SnapshotableAggregateInterface $aggregate): void
    {
        $snapshotData = $aggregate->createSnapshot();

        $this->connection->insert('aggregate_snapshots', [
            'aggregate_id' => $aggregate->getAggregateId(),
            'aggregate_type' => $this->getAggregateType($aggregate),
            'version' => $aggregate->aggregateVersion(),
            'data' => json_encode($snapshotData),
            'created_at' => UtcClock::immutableNow()->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function loadSnapshot(string $aggregateId, string $aggregateType): ?array
    {
        $data = $this->connection->fetchAssociative(
            'SELECT * FROM aggregate_snapshots 
             WHERE aggregate_id = ? AND aggregate_type = ? 
             ORDER BY version DESC LIMIT 1',
            [$aggregateId, $aggregateType]
        );

        if (!$data) {
            return null;
        }

        return [
            'data' => json_decode($data['data'], true),
            'version' => (int) $data['version'],
        ];
    }

    private function getAggregateType(AggregateRootInterface $aggregate): string
    {
        return new \ReflectionClass($aggregate)->getShortName();
    }
}
