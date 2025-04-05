<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\QueryHandlers;

use App\BudgetPlanContext\Application\Queries\GetABudgetPlanWithItsEntriesQuery;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanNotFoundException;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanCategoriesTranslatorInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;

final readonly class GetABudgetPlanWithItsEntriesQueryHandler
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        private BudgetPlanCategoriesTranslatorInterface $budgetPlanCategoriesTranslator,
    ) {
    }

    /**
     * @throws BudgetPlanNotFoundException
     */
    public function __invoke(GetABudgetPlanWithItsEntriesQuery $query): array
    {
        $budgetPlan = $this->budgetPlanViewRepository->findOnePlanWithEntriesBy(
            [
                'uuid' => (string) $query->getBudgetPlanId(),
                'user_uuid' => (string) $query->getBudgetPlanUserId(),
                'is_deleted' => false,
            ]
        );

        if ([] === $budgetPlan) {
            throw new BudgetPlanNotFoundException();
        }

        return $this->budgetPlanCategoriesTranslator->translate(
            $budgetPlan,
            $query->getUserLanguagePreference(),
        );
    }
}
