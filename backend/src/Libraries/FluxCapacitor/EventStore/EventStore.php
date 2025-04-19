<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\PublishDomainEventsException;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventPublisherInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventClassMapInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\UserAggregateInterface;
use App\Libraries\FluxCapacitor\EventStore\Services\RequestIdProvider;
use App\Libraries\FluxCapacitor\EventStore\Traits\AggregateTrackerTrait;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

final class EventStore implements EventStoreInterface
{
    use AggregateTrackerTrait;

    public function __construct(
        private Connection $connection,
        private DomainEventPublisherInterface $publisher,
        private RequestIdProvider $requestIdProvider,
        private EventClassMapInterface $eventClassMap,
        private EventEncryptorInterface $eventEncryptor,
    ) {
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): AggregateRootInterface
    {
        $queryBuilder = $this->createBaseQueryBuilder($uuid, $desiredDateTime);
        $eventsIterator = $queryBuilder->executeQuery()->iterateAssociative();
        $aggregate = $this->createAggregateFromEvents($eventsIterator, $uuid);

        if ($desiredDateTime instanceof \DateTimeImmutable) {
            $aggregateVersion = $this->connection->fetchOne(
                'SELECT MAX(stream_version) FROM event_store WHERE stream_id = :id',
                ['id' => $uuid],
            );
            $aggregate->setAggregateVersion((int) $aggregateVersion);
        }

        $this->trackAggregate($aggregate);

        return $aggregate;
    }

    #[\Override]
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
    public function saveMultiAggregate(array $aggregates): void
    {
        try {
            $this->connection->beginTransaction();

            foreach ($aggregates as $aggregate) {
                $this->save($aggregate);
            }

            $this->connection->commit();
        } catch (Exception) {
            $this->connection->rollBack();
            throw new PublishDomainEventsException();
        }
    }

    #[\Override]
    public function save(AggregateRootInterface $aggregate): void
    {
        try {
            $this->connection->beginTransaction();

            foreach ($aggregate->raisedDomainEvents() as $event) {
                $version = $aggregate->aggregateVersion();

                if (is_subclass_of($event::class, UserDomainEventInterface::class)) {
                    $event = $this->eventEncryptor->encrypt($event, $event->userId);
                }
                $event->requestId = $this->requestIdProvider->requestId;

                $this->connection->insert('event_store', [
                    'stream_id' => $event->aggregateId,
                    'stream_name' => $this->eventClassMap->getStreamNameByEventPath($event::class),
                    'event_name' => $this->eventClassMap->getClassNameByEventPath($event::class),
                    'payload' => json_encode($event->toArray(), JSON_THROW_ON_ERROR),
                    'occurred_on' => $event->occurredOn->format(\DateTimeImmutable::ATOM),
                    'stream_version' => ++$version,
                    'request_id' => $event->requestId,
                    'user_id' => $event->userId,
                    'meta_data' => json_encode([], JSON_THROW_ON_ERROR),
                ]);
            }

            $this->publisher->publishDomainEvents($aggregate->raisedDomainEvents());
            $this->connection->commit();
            $aggregate->clearRaisedDomainEvents();

            if ($aggregate instanceof UserAggregateInterface) {
                $aggregate->clearKeys();
            }

        } catch (Exception $e) {
            $this->connection->rollBack();
            throw new PublishDomainEventsException();
        }
        $this->untrackAggregate($aggregate);
    }

    private function createBaseQueryBuilder(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): QueryBuilder
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('stream_id', 'event_name', 'payload', 'occurred_on', 'request_id', 'user_id', 'stream_version', 'stream_name')
            ->from('event_store')
            ->where('stream_id = :id')
            ->setParameter('id', $uuid)
            ->orderBy('stream_version', 'ASC');

        if (null !== $desiredDateTime) {
            $queryBuilder->andWhere('occurred_on <= :desiredDateTime')
                ->setParameter('desiredDateTime', $desiredDateTime->format('Y-m-d H:i:s'));
        }

        return $queryBuilder;
    }

    private function createAggregateFromEvents(
        \Traversable $eventsIterator,
        string $uuid,
    ): AggregateRootInterface {
        $eventsIterator->rewind();

        if (!$eventsIterator->valid()) {
            $errorMessage = "No events found for aggregate {$uuid}";
            throw new EventsNotFoundForAggregateException($errorMessage);
        }

        $firstEvent = $eventsIterator->current();
        $streamName = $firstEvent['stream_name'];
        /**@var AggregateRootInterface $aggregatePath **/
        $aggregatePath = $this->eventClassMap->getAggregatePathByByStreamName($streamName);
        $aggregate = $aggregatePath::empty();
        $version = (int) $firstEvent['stream_version'];
        $this->processEventForAggregate($firstEvent, $aggregate);
        $eventsIterator->next();

        while ($eventsIterator->valid()) {
            $event = $eventsIterator->current();
            $this->processEventForAggregate($event, $aggregate);
            $version = (int) $event['stream_version'];
            $eventsIterator->next();
        }

        $aggregate->setAggregateVersion($version);

        return $aggregate;
    }

    private function processEventForAggregate(array $eventData, AggregateRootInterface $aggregate): void
    {
        $eventPath = $this->eventClassMap->getEventPathByClassName($eventData['event_name']);
        $payload = $this->getEventPayload($eventData, $eventPath);
        $domainEvent = $eventPath::fromArray($payload);
        $methodName = sprintf('apply%s', new \ReflectionClass($domainEvent)->getShortName());
        $aggregate->$methodName($domainEvent);
    }

    private function getEventPayload(array $eventData, string $eventPath): array
    {
        if (is_subclass_of($eventPath, UserDomainEventInterface::class)) {
            $decodedPayload = json_decode(
                $eventData['payload'],
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $decryptedObject = $this->eventEncryptor->decrypt($eventPath::fromArray($decodedPayload), $eventData['user_id']);

            return $decryptedObject->toArray();
        }

        return json_decode($eventData['payload'], true, 512, JSON_THROW_ON_ERROR);
    }
}
