<?php

namespace App\UserContext\Domain\Events;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\VersionedDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\VersionedEventTrait;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class UserDeletedDomainEvent_v1 implements VersionedDomainEventInterface
{
    use VersionedEventTrait;

    public const int VERSION = 1;
    public const string EVENT_TYPE = 'UserDeleted';

    public string $aggregateId;
    public string $userId;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $userId,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->requestId = $requestId;
        $this->occurredOn = UtcClock::immutableNow();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'requestId' => $this->requestId,
            'userId' => $this->userId,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['userId'], $data['requestId']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
