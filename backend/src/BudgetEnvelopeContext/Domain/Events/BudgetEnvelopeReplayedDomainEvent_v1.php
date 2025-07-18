<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\VersionedDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\VersionedEventTrait;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetEnvelopeReplayedDomainEvent_v1 implements VersionedDomainEventInterface
{
    use VersionedEventTrait;

    public const int VERSION = 1;
    public const string EVENT_TYPE = 'BudgetEnvelopeReplayed';
    
    public string $aggregateId;
    public string $userId;
    public string $name;
    public string $targetedAmount;
    public string $currentAmount;
    public string $currency;
    public bool $isDeleted;
    public \DateTime $updatedAt;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $userId,
        string $name,
        string $targetedAmount,
        string $currentAmount,
        string $currency,
        string $updatedAt,
        bool $isDeleted,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->name = $name;
        $this->targetedAmount = $targetedAmount;
        $this->currentAmount = $currentAmount;
        $this->currency = $currency;
        $this->updatedAt = new \DateTime($updatedAt);
        $this->requestId = $requestId;
        $this->isDeleted = $isDeleted;
        $this->occurredOn = UtcClock::immutableNow();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'requestId' => $this->requestId,
            'userId' => $this->userId,
            'name' => $this->name,
            'targetedAmount' => $this->targetedAmount,
            'currentAmount' => $this->currentAmount,
            'currency' => $this->currency,
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
            'isDeleted' => $this->isDeleted,
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['userId'],
            $data['name'],
            $data['targetedAmount'],
            $data['currentAmount'],
            $data['currency'],
            $data['updatedAt'],
            $data['isDeleted'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
