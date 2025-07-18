<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent_v1;

interface BudgetPlanWantEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanWantAddedDomainEvent_v1(BudgetPlanWantAddedDomainEvent_v1 $event): self;

    public static function fromRepository(array $budgetPlanWantEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
