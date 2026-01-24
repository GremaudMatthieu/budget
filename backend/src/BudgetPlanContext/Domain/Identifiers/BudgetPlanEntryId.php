<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Identifiers;

use App\SharedContext\Domain\Ports\Inbound\AggregateId;
use Assert\Assert;

final readonly class BudgetPlanEntryId implements AggregateId
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
