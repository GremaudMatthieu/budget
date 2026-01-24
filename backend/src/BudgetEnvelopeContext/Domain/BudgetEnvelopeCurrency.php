<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain;

use Assert\Assert;

final readonly class BudgetEnvelopeCurrency
{
    public const array AVAILABLE_CURRENCIES = ['USD', 'EUR', 'GBP'];

    private function __construct(protected string $budgetEnvelopeCurrency)
    {
        Assert::that($budgetEnvelopeCurrency)
            ->notBlank('Currency should not be blank.')
            ->regex('/^[A-Z]{3}$/', 'The currency must be a 3-letter uppercase code.')
            ->inArray(
                self::AVAILABLE_CURRENCIES,
                'The currency must be one of the following: USD, EUR, GBP.',
            )
        ;
    }

    public static function fromString(string $budgetEnvelopeCurrency): self
    {
        return new self($budgetEnvelopeCurrency);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeCurrency;
    }
}
