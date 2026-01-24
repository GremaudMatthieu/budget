<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\Queries;

use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanNotFound;
use App\Presentation\BudgetPlan\BudgetPlanFinder;
use App\Presentation\BudgetPlan\Infrastructure\BudgetPlanCategoriesTranslator;

final readonly class GetABudgetPlanWithItsEntriesHandler
{
    public function __construct(
        private BudgetPlanFinder $budgetPlanViewRepository,
        private BudgetPlanCategoriesTranslator $budgetPlanCategoriesTranslator,
    ) {
    }

    /**
     * @throws BudgetPlanNotFound
     */
    public function __invoke(GetABudgetPlanWithItsEntries $query): array
    {
        $budgetPlan = $this->budgetPlanViewRepository->findOnePlanWithEntriesBy(
            [
                'uuid' => (string) $query->getBudgetPlanId(),
                'user_uuid' => (string) $query->getBudgetPlanUserId(),
                'is_deleted' => false,
            ]
        );

        if ([] === $budgetPlan) {
            throw new BudgetPlanNotFound();
        }

        return $this->budgetPlanCategoriesTranslator->translate(
            $budgetPlan,
            $query->getUserLanguagePreference(),
        );
    }
}
