<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\ValueObjects;

use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use Assert\Assert;

final readonly class BudgetEnvelopeNameRegistryId
{
    public function __construct(protected string $uuid)
    {
        Assert::that($uuid)
            ->notBlank('UUID should not be blank.')
            ->uuid('Invalid UUID format.')
        ;
    }

    public static function fromUserIdAndBudgetEnvelopeName(
        UserId $userId,
        BudgetEnvelopeName $budgetEnvelopeName,
        UuidGeneratorInterface $uuidGenerator,
    ): self {
        return new self($uuidGenerator::uuidV5((string) $userId, (string) $budgetEnvelopeName));
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
