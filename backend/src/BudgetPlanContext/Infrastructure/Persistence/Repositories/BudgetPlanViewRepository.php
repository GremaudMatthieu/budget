<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlansPaginatedInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlansPaginated;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class BudgetPlanViewRepository implements BudgetPlanViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function save(BudgetPlanViewInterface $budgetPlanView): void
    {
        $this->connection->executeStatement('
            INSERT INTO budget_plan_view (uuid, user_uuid, date, created_at, updated_at)
            VALUES (:uuid, :user_uuid, :date, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                user_uuid = VALUES(user_uuid),
                date = VALUES(date),
                updated_at = VALUES(updated_at)
        ', [
            'uuid' => $budgetPlanView->uuid,
            'user_uuid' => $budgetPlanView->userId,
            'date' => $budgetPlanView->date->format(\DateTimeImmutable::ATOM),
            'created_at' => $budgetPlanView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function delete(BudgetPlanViewInterface $budgetPlanView): void
    {
        $this->connection->delete('budget_plan_view', ['uuid' => $budgetPlanView->uuid]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetPlanViewInterface
    {
        $sql = sprintf('SELECT * FROM budget_plan_view WHERE %s LIMIT 1', $this->buildWhereClause($criteria));
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAssociative();

        return $result ? BudgetPlanView::fromRepository($result) : null;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOnePlanWithEntriesBy(array $criteria, ?array $orderBy = null): array
    {
        $sql = sprintf(
            'SELECT pv.*, 
                    nv.uuid AS need_uuid, nv.need_name, nv.need_amount, nv.created_at AS need_created_at, nv.updated_at AS need_updated_at,
                    sv.uuid AS saving_uuid, sv.saving_name, sv.saving_amount, sv.created_at AS saving_created_at, sv.updated_at AS saving_updated_at,
                    wv.uuid AS want_uuid, wv.want_name, wv.want_amount, wv.created_at AS want_created_at, wv.updated_at AS want_updated_at
             FROM budget_plan_view pv
             LEFT JOIN budget_plan_need_entry_view nv ON pv.uuid = nv.budget_plan_uuid
             LEFT JOIN budget_plan_saving_entry_view sv ON pv.uuid = sv.budget_plan_uuid
             LEFT JOIN budget_plan_want_entry_view wv ON pv.uuid = wv.budget_plan_uuid
             WHERE %s',
            $this->buildWhereClauseWithAlias($criteria, 'pv')
        );
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAllAssociative();

        if (!$result) {
            return [];
        }

        $budgetPlanData = $result[0];
        unset(
            $budgetPlanData['need_uuid'], $budgetPlanData['need_name'], $budgetPlanData['need_amount'],$budgetPlanData['need_created_at'], $budgetPlanData['need_updated_at'],
            $budgetPlanData['saving_uuid'], $budgetPlanData['saving_name'], $budgetPlanData['saving_amount'], $budgetPlanData['saving_created_at'], $budgetPlanData['saving_updated_at'],
            $budgetPlanData['want_uuid'], $budgetPlanData['want_name'], $budgetPlanData['want_amount'], $budgetPlanData['want_created_at'], $budgetPlanData['want_updated_at']
        );

        return [
            'plan' => BudgetPlanView::fromRepository($budgetPlanData),
            'needs' => array_values(array_filter(array_map([$this, 'mapToBudgetPlanNeedEntryView'], $result), fn($entry) => $entry !== null)),
            'wants' => array_values(array_filter(array_map([$this, 'mapToBudgetPlanWantEntryView'], $result), fn($entry) => $entry !== null)),
            'savings' => array_values(array_filter(array_map([$this, 'mapToBudgetPlanSavingEntryView'], $result), fn($entry) => $entry !== null)),
        ];
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): BudgetPlansPaginatedInterface {
        $sql = sprintf('SELECT * FROM budget_plan_view WHERE %s', $this->buildWhereClause($criteria));

        if ($orderBy) {
            $sql = sprintf(
                '%s ORDER BY %s',
                $sql,
                implode(
                    ', ',
                    array_map(fn ($key, $value) => sprintf('%s %s', $key, $value), array_keys($orderBy), $orderBy)
                )
            );
        }

        if ($limit) {
            $sql = sprintf('%s LIMIT %d', $sql, $limit);
        }

        if ($offset) {
            $sql = sprintf('%s OFFSET %d', $sql, $offset);
        }

        $stmt = $this->connection->prepare($sql);
        $query = $stmt->executeQuery($this->filterCriteria($criteria));
        $results = $query->fetchAllAssociative();
        $count = $query->rowCount();

        return new BudgetPlansPaginated(
            array_map([$this, 'mapToBudgetPlanView'], $results),
            $count
        );
    }

    private function buildWhereClause(array $criteria): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s IS NULL', $key) : sprintf('%s = :%s', $key, $key), array_keys($criteria), $criteria)
        );
    }

    private function buildWhereClauseWithAlias(array $criteria, string $alias): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s.%s IS NULL', $alias, $key) : sprintf('%s.%s = :%s', $alias, $key, $key), array_keys($criteria), $criteria)
        );
    }

    private function filterCriteria(array $criteria): array
    {
        return array_filter($criteria, fn ($value) => null !== $value);
    }

    private function mapToBudgetPlanView(array $data): BudgetPlanViewInterface
    {
        return BudgetPlanView::fromRepository($data);
    }
}
