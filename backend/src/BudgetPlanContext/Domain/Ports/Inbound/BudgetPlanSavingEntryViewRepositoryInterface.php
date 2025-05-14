<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanSavingEntryViewRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?BudgetPlanSavingEntryViewInterface;

    public function save(BudgetPlanSavingEntryViewInterface $budgetPlanSavingEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
