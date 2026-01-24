<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain;

use Assert\Assert;

final readonly class BudgetEnvelopeEntryType
{
    public const string CREDIT = 'credit';
    public const string DEBIT = 'debit';

    private function __construct(protected string $budgetEnvelopeEntryType)
    {
        Assert::that($budgetEnvelopeEntryType)
            ->notBlank('Monetary type should not be blank.')
            ->string('Monetary type must be a string.')
            ->choice([self::CREDIT, self::DEBIT], 'The entry type must be either "credit" or "debit".')
        ;
    }

    public static function fromString(string $budgetEnvelopeLedgerEntryEntryType): self
    {
        return new self($budgetEnvelopeLedgerEntryEntryType);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeEntryType;
    }
}
