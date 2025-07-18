<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\ValueObjects;

use App\SharedContext\Domain\Ports\Inbound\AggregateIdInterface;
use Assert\Assert;

final readonly class UserId implements AggregateIdInterface
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

    public function equals(UserId $userId): bool
    {
        return (string) $userId === $this->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
