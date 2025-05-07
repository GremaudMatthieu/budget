<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class BudgetEnvelopeEntryDescription
{
    private function __construct(protected string $budgetEnvelopeLedgerEntryDescription)
    {
        Assert::that($budgetEnvelopeLedgerEntryDescription)
            ->maxLength(13, 'The description must be at most 13 characters long.')
            ->regex('/^[\p{L}\p{N} ]+$/u', 'The description can only contain letters (including letters with accents), numbers (0-9), and spaces. No special characters are allowed.')
        ;
    }

    public static function fromString(string $budgetEnvelopeLedgerEntryDescription): self
    {
        return new self($budgetEnvelopeLedgerEntryDescription);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeLedgerEntryDescription;
    }
}
