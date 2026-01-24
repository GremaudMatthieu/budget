<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\Queries;

use App\Presentation\BudgetPlan\BudgetPlanFinder;

final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearHandler
{
    public function __construct(private BudgetPlanFinder $budgetPlanViewRepository)
    {
    }

    public function __invoke(GetACalendarWithItsBudgetPlansFinancialRatiosByYear $query): array
    {
        return $this->budgetPlanViewRepository->getACalendarWithItsBudgetPlansFinancialRatiosByYear(
            [
                'user_uuid' => (string) $query->getBudgetPlanUserId(),
                'year' => $query->getDate()->format('Y'),
                'is_deleted' => false,
            ],
        );
    }
}
