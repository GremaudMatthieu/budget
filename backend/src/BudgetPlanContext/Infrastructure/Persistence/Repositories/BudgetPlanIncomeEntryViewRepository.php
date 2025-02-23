<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewRepositoryInterface;
use Doctrine\DBAL\Connection;

final class BudgetPlanIncomeEntryViewRepository implements BudgetPlanIncomeEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function save(BudgetPlanIncomeEntryViewInterface $budgetPlanIncomeEntryView): void
    {
        $this->connection->executeStatement('
            INSERT INTO budget_plan_income_entry_view (uuid, budget_plan_uuid, income_name, income_amount, created_at, updated_at)
            VALUES (:uuid, :budget_plan_uuid, :income_name, :income_amount, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                income_name = VALUES(income_name),
                income_amount = VALUES(income_amount),
                updated_at = VALUES(updated_at)
        ', [
            'uuid' => $budgetPlanIncomeEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanIncomeEntryView->budgetPlanUuid,
            'income_name' => $budgetPlanIncomeEntryView->incomeName,
            'income_amount' => $budgetPlanIncomeEntryView->incomeAmount,
            'created_at' => $budgetPlanIncomeEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanIncomeEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_income_entry_view', ['uuid' => $uuid]);
    }
}
