<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanWantEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $want,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public function jsonSerialize(): array;
}
