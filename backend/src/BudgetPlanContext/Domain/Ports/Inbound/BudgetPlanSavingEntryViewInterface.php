<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanSavingEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public function jsonSerialize(): array;
}
