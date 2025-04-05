<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

interface BudgetPlanViewInterface
{
    public static function fromRepository(array $budgetPlan): self;

    public static function fromBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): self;

    public static function fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): self;

    public function fromEvent(DomainEventInterface $event): void;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
