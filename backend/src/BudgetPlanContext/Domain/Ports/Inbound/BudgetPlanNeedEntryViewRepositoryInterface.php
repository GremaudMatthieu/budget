<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanNeedEntryViewRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?BudgetPlanNeedEntryViewInterface;

    public function save(BudgetPlanNeedEntryViewInterface $budgetPlanNeedEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
