<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain;

use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeTargetedAmount as BudgetEnvelopeTargetedAmountException;
use Assert\Assert;

final readonly class BudgetEnvelopeTargetedAmount
{
    private function __construct(
        protected string $budgetEnvelopeTargetedAmount,
        protected string $budgetEnvelopeCurrentAmount,
    ) {
        if (floatval($budgetEnvelopeTargetedAmount) <= 0.00) {
            throw BudgetEnvelopeTargetedAmountException::isBelowZero();
        }

        if (floatval($budgetEnvelopeCurrentAmount) > floatval($budgetEnvelopeTargetedAmount)) {
            throw BudgetEnvelopeTargetedAmountException::isBelowCurrentAmount();
        }

        Assert::that($budgetEnvelopeTargetedAmount)
            ->notBlank('Target amount should not be blank.')
            ->string('The target amount must be a string.')
            ->minLength(1, 'The target amount must be at least 1 character long.')
            ->maxLength(13, 'The target amount must be at most 13 character long.')
            ->regex('/^\d+(\.\d{1,2})?$/', 'The target amount must be a string representing a number with up to two decimal places (e.g., "0.00").')
        ;
    }

    public static function fromString(string $budgetEnvelopeTargetedAmount, string $budgetEnvelopeCurrentAmount): self
    {
        return new self($budgetEnvelopeTargetedAmount, $budgetEnvelopeCurrentAmount);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeTargetedAmount;
    }
}
