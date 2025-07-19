<?php

namespace App\UserContext\Domain\Events;

use App\Libraries\FluxCapacitor\Anonymizer\Attributes\PersonalData;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\VersionedDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\VersionedEventTrait;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class UserLastnameChangedDomainEvent_v1 implements VersionedDomainEventInterface
{
    use VersionedEventTrait;

    public const int VERSION = 1;
    public const string EVENT_TYPE = 'UserLastnameChanged';

    public string $aggregateId;
    #[PersonalData]
    public string $lastname;
    public string $userId;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $lastname,
        string $userId,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->aggregateId = $aggregateId;
        $this->lastname = $lastname;
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
            'lastname' => $this->lastname,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['lastname'], $data['userId'], $data['requestId']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
