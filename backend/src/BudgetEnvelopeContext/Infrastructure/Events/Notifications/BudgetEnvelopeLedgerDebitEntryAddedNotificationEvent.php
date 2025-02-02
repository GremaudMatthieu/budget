<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;

final readonly class BudgetEnvelopeLedgerDebitEntryAddedNotificationEvent
{
    public string $aggregateId;
    public string $userId;
    public string $requestId;
    public string $type;

    private function __construct(
        string $aggregateId,
        string $userId,
        string $requestId,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->requestId = $requestId;
        $this->type = 'BudgetEnvelopeLedgerDebitEntryAdded';
    }

    public static function fromDomainEvent(
        BudgetEnvelopeDebitedDomainEvent $budgetEnvelopeDebitedDomainEvent
    ): self {
        return new self(
            $budgetEnvelopeDebitedDomainEvent->aggregateId,
            $budgetEnvelopeDebitedDomainEvent->userId,
            $budgetEnvelopeDebitedDomainEvent->requestId,
        );
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'requestId' => $this->requestId,
            'type' => $this->type,
        ];
    }
}
