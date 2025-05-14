<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanWantEntryViewRepositoryInterface
{
    public function findOneByUuid(string $uuid): ?BudgetPlanWantEntryViewInterface;

    public function save(BudgetPlanWantEntryViewInterface $budgetPlanWantEntryView): void;

    public function delete(string $uuid): void;

    public function deleteByBudgetPlanId(string $uuid): void;
}
