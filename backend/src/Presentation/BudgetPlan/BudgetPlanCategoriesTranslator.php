<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan;

interface BudgetPlanCategoriesTranslator
{
    public function translate(array $budgetPlans, string $locale): array;
}
