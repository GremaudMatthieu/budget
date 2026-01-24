<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain;

use Assert\Assert;

final readonly class BudgetEnvelopeDebitMoney
{
    private function __construct(protected string $budgetEnvelopeDebitMoney)
    {
        Assert::that($budgetEnvelopeDebitMoney)
            ->notBlank('Debit money should not be blank.')
            ->string('Debit money must be a string.')
            ->minLength(1, 'The debit money must be at least 1 character long.')
            ->maxLength(13, 'The debit money must be at most 13 character long.')
            ->regex('/^\d+(\.\d{1,2})?$/')
        ;
    }

    public static function fromString(string $budgetEnvelopeDebitMoney): self
    {
        return new self($budgetEnvelopeDebitMoney);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeDebitMoney;
    }
}
