<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent_v1;

interface BudgetPlanNeedEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent_v1(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent_v1(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanNeedAddedDomainEvent_v1(BudgetPlanNeedAddedDomainEvent_v1 $event): self;

    public static function fromRepository(array $budgetPlanNeedEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
