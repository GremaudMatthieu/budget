<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Events;

use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;

final class BudgetPlanIncomeAddedDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $uuid;
    public string $userId;
    public string $amount;
    public string $category;
    public string $name;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $uuid,
        string $userId,
        string $amount,
        string $name,
        string $category,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->uuid = $uuid;
        $this->amount = $amount;
        $this->category = $category;
        $this->name = $name;
        $this->requestId = $requestId;
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'uuid' => $this->uuid,
            'amount' => $this->amount,
            'name' => $this->name,
            'category' => $this->category,
            'requestId' => $this->requestId,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['uuid'],
            $data['userId'],
            $data['amount'],
            $data['name'],
            $data['category'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
