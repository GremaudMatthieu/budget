<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;

interface BudgetPlanSavingEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        array $saving,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanSavingAddedDomainEvent(BudgetPlanSavingAddedDomainEvent $event): self;

    public static function fromRepository(array $budgetPlanSavingEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
