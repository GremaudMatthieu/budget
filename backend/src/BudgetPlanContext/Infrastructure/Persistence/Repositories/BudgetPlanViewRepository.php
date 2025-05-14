<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanNeedEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanWantEntryView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class BudgetPlanViewRepository implements BudgetPlanViewRepositoryInterface
{
    private Connection $connection;
    private const array BOOLEAN_FIELDS = ['is_deleted'];

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
        INSERT INTO budget_plan_view (uuid, user_uuid, date, currency, created_at, updated_at, is_deleted, context_uuid, context)
        VALUES (:uuid, :user_uuid, :date, :currency, :created_at, :updated_at, :is_deleted, :context_uuid, :context)
        ON CONFLICT (uuid) DO UPDATE SET
            user_uuid = EXCLUDED.user_uuid,
            date = EXCLUDED.date,
            currency = EXCLUDED.currency,
            updated_at = EXCLUDED.updated_at,
            is_deleted = EXCLUDED.is_deleted,
            context_uuid = EXCLUDED.context_uuid,
            context = EXCLUDED.context
        ', [
            'uuid' => $budgetPlanView->uuid,
            'user_uuid' => $budgetPlanView->userId,
            'date' => $budgetPlanView->date->format(\DateTimeImmutable::ATOM),
            'currency' => $budgetPlanView->currency,
            'created_at' => $budgetPlanView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanView->updatedAt->format(\DateTime::ATOM),
            'is_deleted' => $budgetPlanView->isDeleted ? '1' : '0',
            'context_uuid' => $budgetPlanView->contextUuid,
            'context' => $budgetPlanView->context,
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
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('budget_plan_view')
            ->setMaxResults(1);
        $this->addWhereClauses($qb, $criteria);
        $this->addOrderByClauses($qb, $orderBy);
        $result = $qb->executeQuery()->fetchAssociative();

        return $result ? BudgetPlanView::fromRepository($result) : null;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOnePlanWithEntriesBy(array $criteria, ?array $orderBy = null): array
    {
        $sql = '
        SELECT pv.*,
        (SELECT json_agg(
            json_build_object(
                \'uuid\', nv.uuid,
                \'budget_plan_uuid\', nv.budget_plan_uuid,
                \'need_name\', nv.need_name,
                \'need_amount\', nv.need_amount,
                \'category\', nv.category,
                \'created_at\', nv.created_at,
                \'updated_at\', nv.updated_at
            )
        ) FROM budget_plan_need_entry_view nv WHERE pv.uuid = nv.budget_plan_uuid) AS needs,
        
        (SELECT json_agg(
            json_build_object(
                \'uuid\', sv.uuid,
                \'budget_plan_uuid\', sv.budget_plan_uuid,
                \'saving_name\', sv.saving_name,
                \'saving_amount\', sv.saving_amount,
                \'category\', sv.category,
                \'created_at\', sv.created_at,
                \'updated_at\', sv.updated_at
            )
        ) FROM budget_plan_saving_entry_view sv WHERE pv.uuid = sv.budget_plan_uuid) AS savings,
        
        (SELECT json_agg(
            json_build_object(
                \'uuid\', wv.uuid,
                \'budget_plan_uuid\', wv.budget_plan_uuid,
                \'want_name\', wv.want_name,
                \'want_amount\', wv.want_amount,
                \'category\', wv.category,
                \'created_at\', wv.created_at,
                \'updated_at\', wv.updated_at
            )
        ) FROM budget_plan_want_entry_view wv WHERE pv.uuid = wv.budget_plan_uuid) AS wants,
        
        (SELECT json_agg(
            json_build_object(
                \'uuid\', iv.uuid,
                \'budget_plan_uuid\', iv.budget_plan_uuid,
                \'income_name\', iv.income_name,
                \'income_amount\', iv.income_amount,
                \'category\', iv.category,
                \'created_at\', iv.created_at,
                \'updated_at\', iv.updated_at
            )
        ) FROM budget_plan_income_entry_view iv WHERE pv.uuid = iv.budget_plan_uuid) AS incomes
        
        FROM budget_plan_view pv
        WHERE ';

        $sql .= $this->buildWhereClauseWithAlias($criteria, 'pv');

        if ($orderBy) {
            $orderByClauses = [];
            foreach ($orderBy as $field => $direction) {
                $orderByClauses[] = "{$field} {$direction}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderByClauses);
        }

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($this->processCriteria($criteria))->fetchAssociative();

        if (!$result) {
            return [];
        }

        $incomes = json_decode($result['incomes'] ?? '[]', true);
        $needs = json_decode($result['needs'] ?? '[]', true);
        $savings = json_decode($result['savings'] ?? '[]', true);
        $wants = json_decode($result['wants'] ?? '[]', true);

        $incomeCategories = $this->calculateCategoryData($incomes, 'income_amount');
        $needCategories = $this->calculateCategoryData($needs, 'need_amount');
        $savingCategories = $this->calculateCategoryData($savings, 'saving_amount');
        $wantCategories = $this->calculateCategoryData($wants, 'want_amount');

        return [
            'budgetPlan' => BudgetPlanView::fromRepository($result),
            'needs' => array_map([$this, 'mapToBudgetPlanNeedEntryView'], $needs ?: []),
            'savings' => array_map([$this, 'mapToBudgetPlanSavingEntryView'], $savings ?: []),
            'wants' => array_map([$this, 'mapToBudgetPlanWantEntryView'], $wants ?: []),
            'incomes' => array_map([$this, 'mapToBudgetPlanIncomeEntryView'], $incomes ?: []),
            'incomeCategoriesRatio' => $incomeCategories['ratios'],
            'incomesTotal' => $incomeCategories['totals'],
            'needCategoriesRatio' => $needCategories['ratios'],
            'needsTotal' => $needCategories['totals'],
            'savingCategoriesRatio' => $savingCategories['ratios'],
            'savingsTotal' => $savingCategories['totals'],
            'wantCategoriesRatio' => $wantCategories['ratios'],
            'wantsTotal' => $wantCategories['totals'],
        ];
    }

    private function calculateCategoryData(array $entries, string $amountField): array
    {
        $totals = [];
        $ratios = [];

        foreach ($entries as $entry) {
            $category = $entry['category'] ?? 'Uncategorized';
            $amount = (float)$entry[$amountField];

            if (!isset($totals[$category])) {
                $totals[$category] = 0;
            }
            $totals[$category] += $amount;
        }

        $totalAmount = array_sum($totals);

        if ($totalAmount > 0) {
            foreach ($totals as $category => $amount) {
                $ratios[$category] = $this->formatPercentage($amount / $totalAmount);
            }
        }

        return [
            'totals' => $totals,
            'ratios' => $ratios
        ];
    }

    private function formatPercentage(float $value): string
    {
        return round($value * 100) . ' %';
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function getACalendarWithItsBudgetPlansFinancialRatiosByYear(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $sql = '
    WITH budget_plans AS (
        SELECT
            EXTRACT(YEAR FROM pv.date) AS year,
            EXTRACT(MONTH FROM pv.date) AS month,
            pv.uuid,
            pv.currency,
            (SELECT COALESCE(SUM(CAST(income_amount AS DECIMAL)), 0) 
             FROM budget_plan_income_entry_view 
             WHERE budget_plan_uuid = pv.uuid) AS total_income,
            (SELECT COALESCE(SUM(CAST(need_amount AS DECIMAL)), 0) 
             FROM budget_plan_need_entry_view 
             WHERE budget_plan_uuid = pv.uuid) AS total_needs,
            (SELECT COALESCE(SUM(CAST(want_amount AS DECIMAL)), 0) 
             FROM budget_plan_want_entry_view 
             WHERE budget_plan_uuid = pv.uuid) AS total_wants,
            (SELECT COALESCE(SUM(CAST(saving_amount AS DECIMAL)), 0) 
             FROM budget_plan_saving_entry_view 
             WHERE budget_plan_uuid = pv.uuid) AS total_savings
        FROM budget_plan_view pv
        WHERE ' . $this->buildWhereClauseWithAlias($criteria, 'pv') . '
        AND pv.user_uuid = :user_uuid
    )
    SELECT 
        bp.*,
        (bp.total_needs + bp.total_wants + bp.total_savings) AS total_allocated,
        CASE WHEN bp.total_income > 0 
             THEN ROUND((bp.total_needs / bp.total_income) * 100, 1) 
             ELSE 0 
        END AS needs_percentage,
        CASE WHEN bp.total_income > 0 
             THEN ROUND((bp.total_wants / bp.total_income) * 100, 1) 
             ELSE 0 
        END AS wants_percentage,
        CASE WHEN bp.total_income > 0 
             THEN ROUND((bp.total_savings / bp.total_income) * 100, 1) 
             ELSE 0 
        END AS savings_percentage,
        CASE WHEN bp.total_income > 0 
             THEN ROUND(((bp.total_needs + bp.total_wants + bp.total_savings) / bp.total_income) * 100, 1) 
             ELSE 0 
        END AS allocated_percentage
    FROM budget_plans bp';

        if ($orderBy) {
            $orderByClauses = [];
            foreach ($orderBy as $field => $direction) {
                $orderByClauses[] = "{$field} {$direction}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderByClauses);
        } else {
            $sql .= ' ORDER BY year DESC, month ASC';
        }

        $results = $this->connection->prepare($sql)->executeQuery($this->processCriteria($criteria))->fetchAllAssociative();
        $formattedResults = [];
        $yearlyTotals = [
            'income' => 0,
            'needs' => 0,
            'wants' => 0,
            'savings' => 0
        ];
        $year = $criteria['year'] ?? date('Y');
        $formattedResults[$year] = array_fill(1, 12, ['uuid' => null]);

        foreach ($results as $result) {
            $year = (int) $result['year'];
            $month = (int) $result['month'];
            $planUuid = $result['uuid'];
            $totalIncome = (float) $result['total_income'];
            $totalNeeds = (float) $result['total_needs'];
            $totalWants = (float) $result['total_wants'];
            $totalSavings = (float) $result['total_savings'];
            $totalAllocated = (float) $result['total_allocated'];
            $formattedResults[$year][$month] = [
                'uuid' => $planUuid,
                'totalIncome' => $totalIncome,
                'totalAllocated' => $totalAllocated,
                'allocatedPercentage' => (float) $result['allocated_percentage'],
                'needsPercentage' => (float) $result['needs_percentage'],
                'wantsPercentage' => (float) $result['wants_percentage'],
                'savingsPercentage' => (float) $result['savings_percentage'],
                'currency' => $result['currency'],
            ];
            $yearlyTotals['income'] += $totalIncome;
            $yearlyTotals['needs'] += $totalNeeds;
            $yearlyTotals['wants'] += $totalWants;
            $yearlyTotals['savings'] += $totalSavings;
        }

        $totalYearlyIncome = $yearlyTotals['income'];

        if ($totalYearlyIncome > 0) {
            $actualNeedsPercentage = round(($yearlyTotals['needs'] / $totalYearlyIncome) * 100);
            $actualWantsPercentage = round(($yearlyTotals['wants'] / $totalYearlyIncome) * 100);
            $actualSavingsPercentage = round(($yearlyTotals['savings'] / $totalYearlyIncome) * 100);
        } else {
            $actualNeedsPercentage = 0;
            $actualWantsPercentage = 0;
            $actualSavingsPercentage = 0;
        }

        $formattedResults['budgetSummary'] = [
            '50/30/20Rule' => [
                'recommended' => [
                    'needs' => 50,
                    'wants' => 30,
                    'savings' => 20
                ],
                'current' => [
                    'needs' => $actualNeedsPercentage,
                    'wants' => $actualWantsPercentage,
                    'savings' => $actualSavingsPercentage
                ]
            ],
            'yearlyTotals' => [
                'income' => round($yearlyTotals['income'], 2),
                'allocated' => round($yearlyTotals['needs'] + $yearlyTotals['wants'] + $yearlyTotals['savings'], 2),
                'needs' => round($yearlyTotals['needs'], 2),
                'wants' => round($yearlyTotals['wants'], 2),
                'savings' => round($yearlyTotals['savings'], 2)
            ]
        ];

        return $formattedResults;
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
    ): array {
        $qb = $this->connection->createQueryBuilder()
            ->select('uuid', 'date')
            ->from('budget_plan_view');

        $this->addWhereClauses($qb, $criteria);
        $this->addOrderByClauses($qb, $orderBy);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function addWhereClauses(QueryBuilder $qb, array $criteria): void
    {
        $processedCriteria = $this->processCriteria($criteria);

        foreach ($processedCriteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere($qb->expr()->isNull($field));
            } else if ($field === 'year') {
                $qb->andWhere('EXTRACT(YEAR FROM date) = :year');
                $qb->setParameter('year', $value);
            } else {
                $qb->andWhere($qb->expr()->eq($field, ":$field"));
                $qb->setParameter($field, $value);
            }
        }
    }

    private function addOrderByClauses(QueryBuilder $qb, ?array $orderBy): void
    {
        if (!$orderBy) {
            return;
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy($field, $direction);
        }
    }

    private function buildWhereClauseWithAlias(array $criteria, string $alias): string
    {
        $processed = $this->processCriteria($criteria);
        $clauses = [];

        foreach ($processed as $field => $value) {
            if ($value === null) {
                $clauses[] = "{$alias}.{$field} IS NULL";
            } else if ($field === 'year') {
                $clauses[] = "EXTRACT(YEAR FROM {$alias}.date) = :{$field}";
            } else {
                $clauses[] = "{$alias}.{$field} = :{$field}";
            }
        }

        return !empty($clauses) ? implode(' AND ', $clauses) : '1=1';
    }

    private function processCriteria(array $criteria): array
    {
        $processed = [];

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (in_array($field, self::BOOLEAN_FIELDS, true)) {
                $processed[$field] = is_bool($value) ? ($value ? '1' : '0') : '0';
            } else {
                $processed[$field] = $value;
            }
        }

        if (!isset($processed['is_deleted']) && !isset($criteria['is_deleted'])) {
            $processed['is_deleted'] = '0';
        }

        return $processed;
    }

    private function mapToBudgetPlanNeedEntryView(array $data): BudgetPlanNeedEntryView
    {
        return BudgetPlanNeedEntryView::fromRepository($data);
    }

    private function mapToBudgetPlanWantEntryView(array $data): BudgetPlanWantEntryView
    {
        return BudgetPlanWantEntryView::fromRepository($data);
    }

    private function mapToBudgetPlanSavingEntryView(array $data): BudgetPlanSavingEntryView
    {
        return BudgetPlanSavingEntryView::fromRepository($data);
    }

    private function mapToBudgetPlanIncomeEntryView(array $data): BudgetPlanIncomeEntryView
    {
        return BudgetPlanIncomeEntryView::fromRepository($data);
    }
}
