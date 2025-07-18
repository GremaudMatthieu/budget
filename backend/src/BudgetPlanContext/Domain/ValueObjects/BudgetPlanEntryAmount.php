<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class BudgetPlanEntryAmount
{
    private function __construct(
        protected string $entryAmount,
    ) {
        Assert::that($entryAmount)
            ->notBlank('Amount should not be blank.')
            ->string('Amount must be a string.')
            ->regex(
                '/^\d+(\.\d{1,2})?$/',
                'The amount must be a string representing a number with up to two decimal places (e.g., "0.00").',
            );
    }

    public static function fromString(string $entryAmount): self
    {
        return new self($entryAmount);
    }

    public function __toString(): string
    {
        return $this->entryAmount;
    }
}
