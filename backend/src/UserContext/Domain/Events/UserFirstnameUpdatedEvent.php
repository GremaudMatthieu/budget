<?php

namespace App\UserContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final class UserFirstnameUpdatedEvent implements EventInterface
{
    public string $aggregateId;
    public string $firstname;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $firstname)
    {
        $this->aggregateId = $aggregateId;
        $this->firstname = $firstname;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'firstname' => $this->firstname,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['firstname']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}