<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeABudgetEnvelopeCurrencyInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(type: 'string')]
        #[Assert\Length(
            min: 1,
            max: 3,
            minMessage: 'envelopes.currencyMinLength',
            maxMessage: 'envelopes.currencyMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^[A-Z]{3}$/',
            message: 'envelopes.currencyInvalid'
        )]
        private(set) string $currency,
    ) {
    }
}
