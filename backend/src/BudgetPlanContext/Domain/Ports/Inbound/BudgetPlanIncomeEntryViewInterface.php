<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanIncomeEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public function jsonSerialize(): array;
}
