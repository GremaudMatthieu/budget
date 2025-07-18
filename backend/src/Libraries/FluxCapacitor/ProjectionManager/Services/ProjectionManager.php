<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\ProjectionManager\Services;

use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;
use App\Libraries\FluxCapacitor\ProjectionManager\Ports\ProjectionManagerInterface;
use App\Libraries\FluxCapacitor\ProjectionManager\Ports\ProjectionRegistryInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

final readonly class ProjectionManager implements ProjectionManagerInterface
{
    public function __construct(
        private EventClassMapInterface $eventClassMap,
        private ProjectionRegistryInterface $projectionRegistry,
        private Connection $connection,
        private ContainerInterface $container,
    ) {
    }

    public function replayProjection(string $projectionClass, ?\DateTimeImmutable $fromDate = null, int $batchSize = 5000): int
    {
        if (!class_exists($projectionClass)) {
            throw new \InvalidArgumentException("Projection class not found: {$projectionClass}");
        }

        if (!$fromDate) {
            $this->resetProjection($projectionClass);
        }

        try {
            $projection = $this->container->get($projectionClass);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Projection service not found: {$projectionClass}. Error: ".$e->getMessage());
        }

        $processed = 0;

        foreach ($this->getAllEventsForProjection($projectionClass, $fromDate, $batchSize) as $eventBatch) {
            $this->connection->beginTransaction();

            try {
                foreach ($eventBatch as $eventData) {
                    $event = $this->reconstructEvent($eventData);
                    $projection($event);
                    ++$processed;
                }

                $this->connection->commit();
            } catch (\Exception $e) {
                $this->connection->rollBack();
                throw new \RuntimeException("Failed to replay batch at event {$eventData['id']} for projection {$projectionClass}: ".$e->getMessage(), 0, $e);
            }
        }

        return $processed;
    }

    public function replayAllProjections(?\DateTimeImmutable $fromDate = null, int $batchSize = 5000): array
    {
        $results = [];

        foreach ($this->getAllProjections() as $projectionClass) {
            $processed = $this->replayProjection($projectionClass, $fromDate, $batchSize);
            $results[$projectionClass] = $processed;
        }

        return $results;
    }

    public function resetProjection(string $projectionClass): void
    {
        $tableName = $this->projectionRegistry->getTableForProjection($projectionClass);

        $tableExists = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?',
            [$tableName]
        );

        if (!$tableExists) {
            throw new \InvalidArgumentException("Table {$tableName} does not exist for projection {$projectionClass}");
        }

        $this->connection->executeStatement("TRUNCATE TABLE {$tableName}");
    }

    public function getProjectionStatus(string $projectionClass): array
    {
        $tableName = $this->projectionRegistry->getTableForProjection($projectionClass);

        $rowCount = $this->connection->fetchOne("SELECT COUNT(*) FROM {$tableName}");

        try {
            $lastUpdate = $this->connection->fetchOne(
                "SELECT MAX(updated_at) FROM {$tableName} WHERE updated_at IS NOT NULL"
            );
        } catch (\Exception) {
            $lastUpdate = null;
        }

        return [
            'projection' => $projectionClass,
            'table' => $tableName,
            'row_count' => (int) $rowCount,
            'last_update' => $lastUpdate,
        ];
    }

    public function getAllProjections(): array
    {
        return $this->projectionRegistry->getAllProjections();
    }

    private function getAllEventsForProjection(string $projectionClass, ?\DateTimeImmutable $fromDate, int $batchSize = 5000): \Generator
    {
        $eventTypes = $this->projectionRegistry->getEventTypesForProjection($projectionClass);
        $offset = 0;

        do {
            $qb = $this->connection->createQueryBuilder()
                ->select('*')
                ->from('event_store')
                ->orderBy('id', 'ASC')
                ->setMaxResults($batchSize)
                ->setFirstResult($offset);

            if (!empty($eventTypes)) {
                $qb->andWhere('event_name IN (:types)')
                   ->setParameter('types', $eventTypes, ArrayParameterType::STRING);
            }

            if ($fromDate) {
                $qb->andWhere('occurred_on >= :from_date')
                   ->setParameter('from_date', $fromDate->format('Y-m-d H:i:s'));
            }

            $batch = $qb->executeQuery()->fetchAllAssociative();

            if (empty($batch)) {
                break;
            }

            yield $batch;
            $offset += $batchSize;

        } while (count($batch) === $batchSize);
    }

    private function reconstructEvent(array $eventData): object
    {
        $eventName = $eventData['event_name'];
        $eventClass = $this->eventClassMap->getEventPathByClassName($eventName);

        if ($eventClass === $eventName) {
            throw new \RuntimeException("Event not found in mapping: {$eventName}. Please add it to event_mappings.yaml");
        }

        if (!class_exists($eventClass)) {
            throw new \RuntimeException("Event class not found: {$eventClass} for event: {$eventName}");
        }

        $payload = json_decode($eventData['payload'], true);

        return $eventClass::fromArray($payload);
    }
}
