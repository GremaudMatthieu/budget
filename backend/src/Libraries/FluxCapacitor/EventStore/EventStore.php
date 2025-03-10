<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore;

use App\Libraries\FluxCapacitor\Exceptions\PublishDomainEventsException;
use App\Libraries\FluxCapacitor\Ports\DomainEventPublisherInterface;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\Libraries\FluxCapacitor\Services\RequestIdProvider;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class EventStore implements EventStoreInterface
{
    public function __construct(
        private Connection $connection,
        private DomainEventPublisherInterface $publisher,
        private RequestIdProvider $requestIdProvider,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): \Generator
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('stream_id', 'event_name', 'payload', 'occurred_on', 'request_id', 'user_id', 'stream_version')
            ->from('event_store')
            ->where('stream_id = :id')
            ->setParameter('id', $uuid)
            ->orderBy('stream_version', 'ASC');

        if (null !== $desiredDateTime) {
            $queryBuilder->andWhere('occurred_on <= :desiredDateTime')
                ->setParameter('desiredDateTime', $desiredDateTime->format('Y-m-d H:i:s'));
        }

        yield from $queryBuilder->executeQuery()->iterateAssociative();
    }

    public function loadByDomainEvents(
        string $uuid,
        array $domainEventClasses,
        ?\DateTimeImmutable $desiredDateTime = null
    ): \Generator {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('stream_id', 'event_name', 'payload', 'occurred_on', 'request_id', 'user_id', 'stream_version')
            ->from('event_store')
            ->where('stream_id = :id')
            ->setParameter('id', $uuid)
            ->andWhere('event_name IN (:domainEventNames)')
            ->setParameter(
                'domainEventNames',
                $this->eventClassMap->getClassNamesByEventsPaths($domainEventClasses),
                ArrayParameterType::STRING,
            )
            ->orderBy('stream_version', 'ASC');

        if (null !== $desiredDateTime) {
            $queryBuilder->andWhere('occurred_on <= :desiredDateTime')
                ->setParameter('desiredDateTime', $desiredDateTime->format('Y-m-d H:i:s'));
        }

        yield from $queryBuilder->executeQuery()->iterateAssociative();
    }

    #[\Override]
    public function save(array $events, int $version): void
    {
        try {
            $this->connection->beginTransaction();

            foreach ($events as $event) {
                $this->connection->insert('event_store', [
                    'stream_id' => $event->aggregateId,
                    'stream_name' => $this->eventClassMap->getStreamNameByEventPath($event::class),
                    'event_name' => $this->eventClassMap->getClassNameByEventPath($event::class),
                    'payload' => json_encode($event->toArray(), JSON_THROW_ON_ERROR),
                    'occurred_on' => $event->occurredOn->format('Y-m-d H:i:s'),
                    'stream_version' => ++$version,
                    'request_id' => $this->requestIdProvider->requestId,
                    'user_id' => $event->userId,
                    'meta_data' => json_encode([], JSON_THROW_ON_ERROR),
                ]);
            }

            $this->publisher->publishDomainEvents($events);
            $this->connection->commit();
        } catch (Exception $exception) {
            $this->connection->rollBack();
            throw new PublishDomainEventsException();
        }
    }

    public function getCurrentVersion(string $aggregateId): int
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('MAX(stream_version) as version')
            ->from('event_store')
            ->where('aggregate_id = :id')
            ->setParameter('id', $aggregateId);

        return (int) $queryBuilder->executeQuery()->fetchOne();
    }
}
