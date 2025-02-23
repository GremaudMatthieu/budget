<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;

final readonly class BudgetPlanGeneratedNotificationEvent
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
        $this->type = 'BudgetPlanGenerated';
    }

    public static function fromDomainEvent(BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent): self
    {
        return new self(
            $budgetPlanGeneratedDomainEvent->aggregateId,
            $budgetPlanGeneratedDomainEvent->userId,
            $budgetPlanGeneratedDomainEvent->requestId,
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
