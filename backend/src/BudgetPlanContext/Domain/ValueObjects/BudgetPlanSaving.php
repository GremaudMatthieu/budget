<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class BudgetPlanSaving
{
    private function __construct(
        protected string $uuid,
        protected string $savingName,
        protected string $category,
        protected string $amount,
    ) {
        Assert::that($uuid)
            ->notBlank('UUID should not be blank.')
            ->uuid('Invalid UUID format.');

        Assert::that($savingName)
            ->notBlank('Saving name should not be blank.')
            ->string('Saving name must be a string.')
            ->minLength(1, 'The saving name must be at least 1 character long.')
            ->maxLength(35, 'The saving name must be at most 35 characters long.');

        Assert::that($category)
            ->notBlank('Category should not be blank.')
            ->string('Category must be a string.')
            ->minLength(1, 'The category must be at least 1 character long.')
            ->maxLength(35, 'The category must be at most 35 characters long.');

        Assert::that($amount)
            ->notBlank('Amount should not be blank.')
            ->string('Amount must be a string.')
            ->regex(
                '/^\d+(\.\d{1,2})?$/',
                'The amount must be a string representing a number with up to two decimal places (e.g., "0.00").',
            );
    }

    public static function fromArray(array $saving): self
    {
        return new self($saving['uuid'], $saving['savingName'], $saving['category'], $saving['amount']);
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSavingName(): string
    {
        return $this->savingName;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->savingName, $this->amount);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'savingName' => $this->savingName,
            'category' => $this->category,
            'amount' => $this->amount,
        ];
    }
}
