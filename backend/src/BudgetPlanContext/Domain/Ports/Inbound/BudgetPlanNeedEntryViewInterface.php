<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent;

interface BudgetPlanNeedEntryViewInterface
{
    public static function fromArrayOnBudgetPlanGeneratedDomainEvent(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        array $need,
        string $budgetPlanUuid,
        \DateTimeImmutable $occurredOn,
    ): self;

    public static function fromBudgetPlanNeedAddedDomainEvent(BudgetPlanNeedAddedDomainEvent $event): self;

    public static function fromRepository(array $budgetPlanNeedEntry): self;

    public function toArray(): array;

    public function jsonSerialize(): array;
}
