<?php

declare(strict_types=1);

namespace App\Presentation\BudgetPlan\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeABudgetPlanCurrencyInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(type: 'string')]
        #[Assert\Length(
            min: 1,
            max: 3,
            minMessage: 'budgetPlan.currencyMinLength',
            maxMessage: 'budgetPlan.currencyMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^[A-Z]{3}$/',
            message: 'budgetPlan.currencyInvalid'
        )]
        private(set) string $currency,
    ) {
    }
}
