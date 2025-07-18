<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent_v1;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

interface BudgetPlanViewInterface
{
    public static function fromRepository(array $budgetPlan): self;

    public static function fromBudgetPlanGeneratedDomainEvent_v1(BudgetPlanGeneratedDomainEvent_v1 $event): self;

    public static function fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1 $event,
    ): self;

    public function fromEvent(DomainEventInterface $event): void;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
