<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanNeedEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public function jsonSerialize(): array;
}
