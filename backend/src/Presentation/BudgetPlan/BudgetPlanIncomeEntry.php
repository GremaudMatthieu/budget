<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan;

use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent_v1;

interface BudgetPlanIncomeEntry
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanIncomeAddedDomainEvent_v1(BudgetPlanIncomeAddedDomainEvent_v1 $event): self;

    public static function fromRepository(array $budgetPlanIncomeEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
