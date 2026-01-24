<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain;

use Assert\Assert;

final readonly class BudgetPlanEntryName
{
    private function __construct(
        protected string $entryName,
    ) {
        Assert::that($entryName)
            ->notBlank('Entry name should not be blank.')
            ->string('Entry name must be a string.')
            ->minLength(1, 'The entry name must be at least 1 character long.')
            ->maxLength(35, 'The entry name must be at most 35 characters long.');
    }

    public static function fromString(string $entryName): self
    {
        return new self($entryName);
    }

    public function __toString(): string
    {
        return $this->entryName;
    }
}
