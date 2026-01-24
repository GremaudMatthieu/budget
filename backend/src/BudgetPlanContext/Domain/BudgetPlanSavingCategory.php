<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain;

use Assert\Assert;

final readonly class BudgetPlanSavingCategory
{
    private function __construct(
        protected string $category,
    ) {
        Assert::that($category)
            ->notBlank('Category should not be blank.')
            ->string('Category must be a string.')
            ->minLength(1, 'The category must be at least 1 character long.')
            ->maxLength(35, 'The category must be at most 35 characters long.');
    }

    public function __toString(): string
    {
        return $this->category;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public static function fromString(string $category): self
    {
        return new self($category);
    }
}
