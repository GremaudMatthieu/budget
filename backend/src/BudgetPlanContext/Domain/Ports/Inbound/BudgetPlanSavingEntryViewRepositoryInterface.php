<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanSavingEntryViewRepositoryInterface
{
    public function save(BudgetPlanSavingEntryViewInterface $budgetPlanSavingEntryView): void;

    public function delete(string $budgetPlanSavingEntryUuid): void;
}
