<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;

final readonly class BudgetPlanSavingAdjustedNotificationEvent
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
        $this->type = 'BudgetPlanSavingAdjusted';
    }

    public static function fromBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): self {
        return new self(
            $budgetPlanGeneratedDomainEvent->aggregateId,
            $budgetPlanGeneratedDomainEvent->userId,
            $budgetPlanGeneratedDomainEvent->requestId,
        );
    }

    public static function fromBudgetPlanSavingAdjustedDomainEvent(
        BudgetPlanSavingAdjustedDomainEvent $budgetPlanSavingAdjustedDomainEvent,
    ): self {
        return new self(
            $budgetPlanSavingAdjustedDomainEvent->aggregateId,
            $budgetPlanSavingAdjustedDomainEvent->userId,
            $budgetPlanSavingAdjustedDomainEvent->requestId,
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
