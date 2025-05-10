<?php

declare(strict_types=1);

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddABudgetEnvelopeInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[Assert\Regex(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        )]
        private(set) string $uuid,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 1,
            max: 50,
            minMessage: 'envelopes.nameMinLength',
            maxMessage: 'envelopes.nameMaxLength'
        )]
        #[Assert\Regex(
            pattern: '/^[\p{L}\p{N} ]+$/u',
            message: 'envelopes.nameInvalid'
        )]
        private(set) string $name,
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
