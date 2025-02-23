<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewRepositoryInterface;
use Doctrine\DBAL\Connection;

final class BudgetPlanWantEntryViewRepository implements BudgetPlanWantEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function save(BudgetPlanWantEntryViewInterface $budgetPlanWantEntryView): void
    {
        $this->connection->executeStatement('
            INSERT INTO budget_plan_want_entry_view (uuid, budget_plan_uuid, want_name, want_amount, created_at, updated_at)
            VALUES (:uuid, :budget_plan_uuid, :want_name, :want_amount, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                want_name = VALUES(want_name),
                want_amount = VALUES(want_amount),
                updated_at = VALUES(updated_at)
        ', [
            'uuid' => $budgetPlanWantEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanWantEntryView->budgetPlanUuid,
            'want_name' => $budgetPlanWantEntryView->wantName,
            'want_amount' => $budgetPlanWantEntryView->wantAmount,
            'created_at' => $budgetPlanWantEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanWantEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_want_entry_view', ['uuid' => $uuid]);
    }
}
