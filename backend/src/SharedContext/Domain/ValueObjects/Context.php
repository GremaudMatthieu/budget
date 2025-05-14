<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\ValueObjects;

use App\SharedContext\Domain\Enums\ContextEnum;
use Assert\Assert;

final readonly class Context
{
    private function __construct(protected string $uuid, protected string $context)
    {
        Assert::that($uuid)
            ->notBlank('UUID should not be blank.')
            ->uuid('Invalid UUID format.')
        ;
        Assert::that($context)
            ->notBlank('Context should not be blank.')
            ->inArray(ContextEnum::values());
    }

    public static function from(string $uuid, string $context): self
    {
        return new self($uuid, $context);
    }

    public function getContextId(): string
    {
        return $this->uuid;
    }

    public function getContext(): string
    {
        return $this->context;
    }
}
