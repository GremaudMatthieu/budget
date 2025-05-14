<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanNeedEntryViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanNeedEntryView;
use Doctrine\DBAL\Connection;

final class BudgetPlanNeedEntryViewRepository implements BudgetPlanNeedEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findOneByUuid(string $uuid): ?BudgetPlanNeedEntryViewInterface
    {
        $sql = 'SELECT uuid, budget_plan_uuid, need_name, need_amount, category, created_at, updated_at
            FROM budget_plan_need_entry_view
            WHERE uuid = :uuid';

        $result = $this->connection->fetchAssociative($sql, ['uuid' => $uuid]);

        if ($result === false) {
            return null;
        }

        return BudgetPlanNeedEntryView::fromRepository($result);
    }

    #[\Override]
    public function save(BudgetPlanNeedEntryViewInterface $budgetPlanNeedEntryView): void
    {
        $this->connection->executeStatement('
        INSERT INTO budget_plan_need_entry_view (uuid, budget_plan_uuid, need_name, need_amount, category, created_at, updated_at)
        VALUES (:uuid, :budget_plan_uuid, :need_name, :need_amount, :category, :created_at, :updated_at)
        ON CONFLICT (uuid) DO UPDATE SET
            need_name = EXCLUDED.need_name,
            need_amount = EXCLUDED.need_amount,
            updated_at = EXCLUDED.updated_at,
            category = EXCLUDED.category
    ', [
            'uuid' => $budgetPlanNeedEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanNeedEntryView->budgetPlanUuid,
            'need_name' => $budgetPlanNeedEntryView->needName,
            'need_amount' => $budgetPlanNeedEntryView->needAmount,
            'category' => $budgetPlanNeedEntryView->category,
            'created_at' => $budgetPlanNeedEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanNeedEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_need_entry_view', ['uuid' => $uuid]);
    }

    #[\Override]
    public function deleteByBudgetPlanId(string $uuid): void
    {
        $this->connection->delete('budget_plan_need_entry_view', ['budget_plan_uuid' => $uuid]);
    }
}
