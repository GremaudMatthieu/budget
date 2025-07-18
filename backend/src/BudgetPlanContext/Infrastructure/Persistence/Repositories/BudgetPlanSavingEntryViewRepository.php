<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use Doctrine\DBAL\Connection;

final class BudgetPlanSavingEntryViewRepository implements BudgetPlanSavingEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findOneByUuid(string $uuid): ?BudgetPlanSavingEntryViewInterface
    {
        $sql = 'SELECT uuid, budget_plan_uuid, saving_name, saving_amount, category, created_at, updated_at
            FROM budget_plan_saving_entry_view
            WHERE uuid = :uuid';

        $result = $this->connection->fetchAssociative($sql, ['uuid' => $uuid]);

        if (false === $result) {
            return null;
        }

        return BudgetPlanSavingEntryView::fromRepository($result);
    }

    #[\Override]
    public function save(BudgetPlanSavingEntryViewInterface $budgetPlanSavingEntryView): void
    {
        $this->connection->executeStatement('
        INSERT INTO budget_plan_saving_entry_view (uuid, budget_plan_uuid, saving_name, saving_amount, category, created_at, updated_at)
        VALUES (:uuid, :budget_plan_uuid, :saving_name, :saving_amount, :category, :created_at, :updated_at)
        ON CONFLICT (uuid) DO UPDATE SET
            saving_name = EXCLUDED.saving_name,
            saving_amount = EXCLUDED.saving_amount,
            updated_at = EXCLUDED.updated_at,
            category = EXCLUDED.category
    ', [
            'uuid' => $budgetPlanSavingEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanSavingEntryView->budgetPlanUuid,
            'saving_name' => $budgetPlanSavingEntryView->savingName,
            'saving_amount' => $budgetPlanSavingEntryView->savingAmount,
            'category' => $budgetPlanSavingEntryView->category,
            'created_at' => $budgetPlanSavingEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanSavingEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_saving_entry_view', ['uuid' => $uuid]);
    }

    #[\Override]
    public function deleteByBudgetPlanId(string $uuid): void
    {
        $this->connection->delete('budget_plan_saving_entry_view', ['budget_plan_uuid' => $uuid]);
    }
}
