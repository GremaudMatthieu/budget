<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Events;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $userId;
    public string $date;
    public string $currency;
    public array $incomes;
    public array $needs;
    public array $wants;
    public array $savings;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $date,
        string $currency,
        array $incomes,
        array $needs,
        array $wants,
        array $savings,
        string $userId,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->date = $date;
        $this->currency = $currency;
        $this->incomes = $incomes;
        $this->needs = $needs;
        $this->wants = $wants;
        $this->savings = $savings;
        $this->requestId = $requestId;
        $this->occurredOn = UtcClock::now();
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'date' => $this->date,
            'currency' => $this->currency,
            'incomes' => $this->incomes,
            'needs' => $this->needs,
            'wants' => $this->wants,
            'savings' => $this->savings,
            'requestId' => $this->requestId,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['date'],
            $data['currency'],
            $data['incomes'],
            $data['needs'],
            $data['wants'],
            $data['savings'],
            $data['userId'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
