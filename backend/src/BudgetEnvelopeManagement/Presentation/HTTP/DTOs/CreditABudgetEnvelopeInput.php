<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreditABudgetEnvelopeInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(type: 'string')]
        #[Assert\Length(
            min: 1,
            max: 13,
            minMessage: 'envelopes.creditMoneyMinLength',
            maxMessage: 'envelopes.creditMoneyMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^\d+(\.\d{2})?$/',
            message: 'envelopes.creditMoneyInvalid'
        )]
        public string $creditMoney,
    ) {
    }
}
