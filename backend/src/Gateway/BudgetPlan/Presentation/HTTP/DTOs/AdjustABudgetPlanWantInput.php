<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AdjustABudgetPlanWantInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(
            min: 3,
            max: 35,
        )]
        private(set) string $name,

        #[Assert\NotBlank]
        #[Assert\Type(type: 'string')]
        #[Assert\Length(
            min: 1,
            max: 13,
            minMessage: 'budgetPlan.amountMinLength',
            maxMessage: 'budgetPlan.amountMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^\d+(\.\d{2})?$/',
            message: 'budgetPlan.amountInvalid'
        )]
        private(set) string $amount,
    ) {
    }
}
