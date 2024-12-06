<?php

namespace App\EnvelopeManagement\Domain\Event;

class EnvelopeDeletedEvent implements EventInterface
{
    private string $aggregateId;
    private bool $isDeleted;
    private \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, bool $isDeleted)
    {
        $this->aggregateId = $aggregateId;
        $this->isDeleted = $isDeleted;
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'isDeleted' => $this->isDeleted,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['isDeleted']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
