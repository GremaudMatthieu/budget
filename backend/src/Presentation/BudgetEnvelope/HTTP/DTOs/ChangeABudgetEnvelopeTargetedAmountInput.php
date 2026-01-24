<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeABudgetEnvelopeTargetedAmountInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type(type: 'string')]
        #[Assert\Length(
            min: 1,
            max: 13,
            minMessage: 'envelopes.targetedAmountMinLength',
            maxMessage: 'envelopes.targetedAmountMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^\d+(\.\d{1,2})?$/',
            message: 'envelopes.targetedAmountInvalid'
        )]
        private(set) string $targetedAmount,
        #[Assert\NotBlank]
        #[Assert\Type(type: 'string')]
        #[Assert\Length(
            min: 1,
            max: 13,
            minMessage: 'envelopes.currentAmountMinLength',
            maxMessage: 'envelopes.currentAmountMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^\d+(\.\d{1,2})?$/',
            message: 'envelopes.currentAmountInvalid'
        )]
        private(set) string $currentAmount,
    ) {
    }
}
