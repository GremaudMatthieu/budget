<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\QueryHandlers;

use App\BudgetPlanContext\Application\Queries\GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanCategoriesTranslatorInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;

final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearQueryHandler
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        private BudgetPlanCategoriesTranslatorInterface $budgetPlanCategoriesTranslator,
    ) {
    }

    public function __invoke(
        GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery $getACalendarWithItsBudgetPlansFinancialRatiosByYearQuery,
    ): array {
        return $this->budgetPlanCategoriesTranslator->translate(
            $this->budgetPlanViewRepository->getACalendarWithItsBudgetPlansFinancialRatiosByYear(
                [
                    'user_uuid' => (string) $getACalendarWithItsBudgetPlansFinancialRatiosByYearQuery->getBudgetPlanUserId(),
                    'year' => $getACalendarWithItsBudgetPlansFinancialRatiosByYearQuery->getDate()->format('Y'),
                    'is_deleted' => false,
                ],
            ),
            $getACalendarWithItsBudgetPlansFinancialRatiosByYearQuery->getUserLanguagePreference(),
        );
    }
}
