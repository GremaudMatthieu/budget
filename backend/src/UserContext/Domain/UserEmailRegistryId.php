<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use Assert\Assert;

final readonly class UserEmailRegistryId
{
    public function __construct(protected string $uuid)
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
