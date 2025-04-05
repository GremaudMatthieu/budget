<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;

interface BudgetPlanIncomeEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        array $income,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanIncomeAddedDomainEvent(BudgetPlanIncomeAddedDomainEvent $event): self;

    public static function fromRepository(array $budgetPlanIncomeEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
