<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;

interface BudgetPlanWantEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanWantAddedDomainEvent(BudgetPlanWantAddedDomainEvent $event): self;

    public static function fromRepository(array $budgetPlanWantEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
