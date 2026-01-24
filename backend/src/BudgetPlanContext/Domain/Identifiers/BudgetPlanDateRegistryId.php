<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Identifiers;

use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use Assert\Assert;

final readonly class BudgetPlanDateRegistryId
{
    public function __construct(protected string $uuid)
    {
        Assert::that($uuid)
            ->notBlank('UUID should not be blank.')
            ->uuid('Invalid UUID format.')
        ;
    }

    public static function fromUserIdAndBudgetPlanDate(
        UserId $userId,
        \DateTimeImmutable $date,
        UuidGenerator $uuidGenerator,
    ): self {
        return new self($uuidGenerator::uuidV5((string) $userId, UtcClock::fromImmutableToString($date)));
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
