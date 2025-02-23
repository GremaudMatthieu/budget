<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewRepositoryInterface;
use Doctrine\DBAL\Connection;

final class BudgetPlanSavingEntryViewRepository implements BudgetPlanSavingEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function save(BudgetPlanSavingEntryViewInterface $budgetPlanSavingEntryView): void
    {
        $this->connection->executeStatement('
            INSERT INTO budget_plan_saving_entry_view (uuid, budget_plan_uuid, saving_name, saving_amount, created_at, updated_at)
            VALUES (:uuid, :budget_plan_uuid, :saving_name, :saving_amount, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                saving_name = VALUES(saving_name),
                saving_amount = VALUES(saving_amount),
                updated_at = VALUES(updated_at)
        ', [
            'uuid' => $budgetPlanSavingEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanSavingEntryView->budgetPlanUuid,
            'saving_name' => $budgetPlanSavingEntryView->savingName,
            'saving_amount' => $budgetPlanSavingEntryView->savingAmount,
            'created_at' => $budgetPlanSavingEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanSavingEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $budgetPlanSavingEntryUuid): void
    {
        $this->connection->delete('budget_plan_saving_entry_view', ['uuid' => $budgetPlanSavingEntryUuid]);
    }
}
