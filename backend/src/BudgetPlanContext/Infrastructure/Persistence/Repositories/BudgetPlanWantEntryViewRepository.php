<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanWantEntryViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanWantEntryView;
use Doctrine\DBAL\Connection;

final class BudgetPlanWantEntryViewRepository implements BudgetPlanWantEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findOneByUuid(string $uuid): ?BudgetPlanWantEntryViewInterface
    {
        $sql = 'SELECT uuid, budget_plan_uuid, want_name, want_amount, category, created_at, updated_at
            FROM budget_plan_want_entry_view
            WHERE uuid = :uuid';

        $result = $this->connection->fetchAssociative($sql, ['uuid' => $uuid]);

        if ($result === false) {
            return null;
        }

        return BudgetPlanWantEntryView::fromRepository($result);
    }

    #[\Override]
    public function save(BudgetPlanWantEntryViewInterface $budgetPlanWantEntryView): void
    {
        $this->connection->executeStatement('
        INSERT INTO budget_plan_want_entry_view (uuid, budget_plan_uuid, want_name, want_amount, category, created_at, updated_at)
        VALUES (:uuid, :budget_plan_uuid, :want_name, :want_amount, :category, :created_at, :updated_at)
        ON CONFLICT (uuid) DO UPDATE SET
            want_name = EXCLUDED.want_name,
            want_amount = EXCLUDED.want_amount,
            updated_at = EXCLUDED.updated_at,
            category = EXCLUDED.category
    ', [
            'uuid' => $budgetPlanWantEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanWantEntryView->budgetPlanUuid,
            'want_name' => $budgetPlanWantEntryView->wantName,
            'want_amount' => $budgetPlanWantEntryView->wantAmount,
            'category' => $budgetPlanWantEntryView->category,
            'created_at' => $budgetPlanWantEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanWantEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_want_entry_view', ['uuid' => $uuid]);
    }

    #[\Override]
    public function deleteByBudgetPlanId(string $uuid): void
    {
        $this->connection->delete('budget_plan_want_entry_view', ['budget_plan_uuid' => $uuid]);
    }
}
