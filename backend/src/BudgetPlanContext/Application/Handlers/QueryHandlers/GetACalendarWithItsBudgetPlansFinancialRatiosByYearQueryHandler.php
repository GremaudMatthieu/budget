<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\QueryHandlers;

use App\BudgetPlanContext\Application\Queries\GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;

final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearQueryHandler
{
    public function __construct(private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository)
    {
    }

    public function __invoke(GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery $query): array
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
