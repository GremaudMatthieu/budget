<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent_v1;

interface BudgetPlanSavingEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanSavingAddedDomainEvent_v1(BudgetPlanSavingAddedDomainEvent_v1 $event): self;

    public static function fromRepository(array $budgetPlanSavingEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
