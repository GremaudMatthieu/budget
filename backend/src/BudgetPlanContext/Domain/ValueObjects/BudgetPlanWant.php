<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\ValueObjects;

use Assert\Assert;

final readonly class BudgetPlanWant
{
    private function __construct(
        protected string $uuid,
        protected string $wantName,
        protected string $category,
        protected string $amount,
    ) {
        Assert::that($uuid)
            ->notBlank('UUID should not be blank.')
            ->uuid('Invalid UUID format.');

        Assert::that($wantName)
            ->notBlank('Want name should not be blank.')
            ->string('Want name must be a string.')
            ->minLength(1, 'The want name must be at least 1 character long.')
            ->maxLength(35, 'The want name must be at most 35 characters long.');

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

    public static function fromArray(array $want): self
    {
        return new self($want['uuid'], $want['wantName'], $want['category'], $want['amount']);
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getWantName(): string
    {
        return $this->wantName;
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
        return sprintf('%s: %s', $this->wantName, $this->amount);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'wantName' => $this->wantName,
            'category' => $this->category,
            'amount' => $this->amount,
        ];
    }
}
