<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;

interface BudgetPlanViewInterface
{
    public static function fromRepository(array $budgetPlan): self;

    public static function fromBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): self;

    public static function fromEvents(\Generator $events): self;

    public function fromEvent(DomainEventInterface $event): void;

    public function jsonSerialize(): array;
}
