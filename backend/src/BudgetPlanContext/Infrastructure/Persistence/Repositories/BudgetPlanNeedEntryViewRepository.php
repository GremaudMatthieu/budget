<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewRepositoryInterface;
use Doctrine\DBAL\Connection;

final class BudgetPlanNeedEntryViewRepository implements BudgetPlanNeedEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function save(BudgetPlanNeedEntryViewInterface $budgetPlanNeedEntryView): void
    {
        $this->connection->executeStatement('
            INSERT INTO budget_plan_need_entry_view (uuid, budget_plan_uuid, need_name, need_amount, created_at, updated_at)
            VALUES (:uuid, :budget_plan_uuid, :need_name, :need_amount, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                need_name = VALUES(need_name),
                need_amount = VALUES(need_amount),
                updated_at = VALUES(updated_at)
        ', [
            'uuid' => $budgetPlanNeedEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanNeedEntryView->budgetPlanUuid,
            'need_name' => $budgetPlanNeedEntryView->needName,
            'need_amount' => $budgetPlanNeedEntryView->needAmount,
            'created_at' => $budgetPlanNeedEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanNeedEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_need_entry_view', ['uuid' => $uuid]);
    }
}
