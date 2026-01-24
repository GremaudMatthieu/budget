<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain;

use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeCurrentAmount as BudgetEnvelopeCurrentAmountException;
use Assert\Assert;

final readonly class BudgetEnvelopeCurrentAmount
{
    private function __construct(
        protected string $budgetEnvelopeCurrentAmount,
        protected string $budgetEnvelopeTargetedAmount,
    ) {
        $currentAmountFloat = floatval($budgetEnvelopeCurrentAmount);

        if ($currentAmountFloat < 0.00) {
            throw BudgetEnvelopeCurrentAmountException::exceedsDebitLimit();
        }

        if ($currentAmountFloat > floatval($budgetEnvelopeTargetedAmount)) {
            throw BudgetEnvelopeCurrentAmountException::exceedsCreditLimit();
        }

        Assert::that($budgetEnvelopeCurrentAmount)
            ->notBlank('Current amount should not be blank.')
            ->string('Current amount must be a string.')
            ->minLength(1, 'The current amount must be at least 1 character long.')
            ->maxLength(13, 'The current amount must be at most 13 character long.')
            ->regex('/^\d+(\.\d{1,2})?$/', 'The current amount must be a string representing a number with up to two decimal places (e.g., "0.00").')
        ;
    }

    public static function fromString(string $budgetEnvelopeCurrentAmount, string $budgetEnvelopeTargetedAmount): self
    {
        return new self($budgetEnvelopeCurrentAmount, $budgetEnvelopeTargetedAmount);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeCurrentAmount;
    }
}
