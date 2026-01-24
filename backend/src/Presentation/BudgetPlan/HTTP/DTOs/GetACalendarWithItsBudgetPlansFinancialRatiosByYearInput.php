<?php

namespace App\Presentation\BudgetPlan\Presentation\HTTP\DTOs;

final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearInput
{
    public function __construct(
        private(set) ?string $year = null,
    ) {
    }
}
