<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\ValueObjects;

use Assert\Assert;

final readonly class BudgetEnvelopeName
{
    private function __construct(protected string $budgetEnvelopeName)
    {
        Assert::that($budgetEnvelopeName)
            ->notBlank('Name should not be blank.')
            ->minLength(1, 'The name must be at least 1 character long.')
            ->maxLength(50, 'The name must be at most 50 characters long.')
            ->regex('/^[\p{L}\p{N} ]+$/u', 'The name can only contain letters (including letters with accents), numbers (0-9), and spaces. No special characters are allowed.')
        ;
    }

    public static function create(string $budgetEnvelopeName): self
    {
        return new self($budgetEnvelopeName);
    }

    public function __toString(): string
    {
        return $this->budgetEnvelopeName;
    }
}