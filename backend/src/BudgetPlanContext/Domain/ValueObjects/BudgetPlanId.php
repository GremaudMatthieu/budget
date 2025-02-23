<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\ValueObjects;

use App\SharedContext\Domain\Ports\Inbound\AggregateIdInterface;
use Assert\Assert;

final readonly class BudgetPlanId implements AggregateIdInterface
{
    private function __construct(protected string $uuid)
    {
        Assert::that($uuid)
            ->notBlank('UUID should not be blank.')
            ->uuid('Invalid UUID format.')
        ;
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
