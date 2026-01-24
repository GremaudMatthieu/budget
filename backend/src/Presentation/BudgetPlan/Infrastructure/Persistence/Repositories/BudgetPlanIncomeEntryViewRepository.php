<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\Infrastructure\Persistence\Repositories;

use App\Presentation\BudgetPlan\Views\BudgetPlanIncomeEntryView;
use App\Presentation\BudgetPlan\BudgetPlanIncomeEntry;
use App\Presentation\BudgetPlan\BudgetPlanIncomeEntryFinder;
use Doctrine\DBAL\Connection;

final class BudgetPlanIncomeEntryViewRepository implements BudgetPlanIncomeEntryFinder
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findOneByUuid(string $uuid): ?BudgetPlanIncomeEntry
    {
        $sql = 'SELECT uuid, budget_plan_uuid, income_name, income_amount, category, created_at, updated_at
            FROM budget_plan_income_entry_view
            WHERE uuid = :uuid';

        $result = $this->connection->fetchAssociative($sql, ['uuid' => $uuid]);

        if (false === $result) {
            return null;
        }

        return BudgetPlanIncomeEntryView::fromRepository($result);
    }

    #[\Override]
    public function save(BudgetPlanIncomeEntry $budgetPlanIncomeEntryView): void
    {
        $this->connection->executeStatement('
        INSERT INTO budget_plan_income_entry_view (uuid, budget_plan_uuid, income_name, income_amount, category, created_at, updated_at)
        VALUES (:uuid, :budget_plan_uuid, :income_name, :income_amount, :category, :created_at, :updated_at)
        ON CONFLICT (uuid) DO UPDATE SET
            income_name = EXCLUDED.income_name,
            income_amount = EXCLUDED.income_amount,
            updated_at = EXCLUDED.updated_at,
            category = EXCLUDED.category
    ', [
            'uuid' => $budgetPlanIncomeEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanIncomeEntryView->budgetPlanUuid,
            'income_name' => $budgetPlanIncomeEntryView->incomeName,
            'income_amount' => $budgetPlanIncomeEntryView->incomeAmount,
            'category' => $budgetPlanIncomeEntryView->category,
            'created_at' => $budgetPlanIncomeEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanIncomeEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_income_entry_view', ['uuid' => $uuid]);
    }

    #[\Override]
    public function deleteByBudgetPlanId(string $uuid): void
    {
        $this->connection->delete('budget_plan_income_entry_view', ['budget_plan_uuid' => $uuid]);
    }
}
