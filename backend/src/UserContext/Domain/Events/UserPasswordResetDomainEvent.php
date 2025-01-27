<?php

namespace App\UserContext\Domain\Events;

use App\UserContext\Domain\Attributes\PersonalData;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;

final class UserPasswordResetDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $password;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $password)
    {
        $this->aggregateId = $aggregateId;
        $this->password = $password;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'password' => $this->password,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['password']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
