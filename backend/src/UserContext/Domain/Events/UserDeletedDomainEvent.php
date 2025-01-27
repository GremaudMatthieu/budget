<?php

namespace App\UserContext\Domain\Events;

use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;

final class UserDeletedDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId)
    {
        $this->aggregateId = $aggregateId;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
