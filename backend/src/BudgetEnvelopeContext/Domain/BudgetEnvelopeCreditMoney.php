<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain;

use Assert\Assert;

final readonly class BudgetEnvelopeCreditMoney
{
    private function __construct(protected string $budgetEnvelopeCreditMoney)
    {
        Assert::that($budgetEnvelopeCreditMoney)
            ->notBlank('Credit money should not be blank.')
            ->string('Credit money must be a string.')
            ->minLength(1, 'The credit money must be at least 1 character long.')
            ->maxLength(13, 'The credit money must be at most 13 character long.')
            ->regex('/^\d+(\.\d{1,2})?$/', 'The credit money must be a string representing a number with up to two decimal places (e.g., "0.00").')
        ;
    }

    public static function fromString(string $budgetEnvelopeCreditMoney): self
    {
        return new self($budgetEnvelopeCreditMoney);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeCreditMoney;
    }
}
