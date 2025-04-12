<?php

namespace App\UserContext\Domain\ValueObjects;

use Assert\Assert;

class UserRegistrationContext
{
    private function __construct(protected string $context)
    {
        Assert::that($context)
            ->notBlank('Registration context should not be blank.')
            ->inArray(['google'], 'Registration context must be "google".');
    }

    public static function fromString(string $context): self
    {
        return new self($context);
    }

    public function __toString(): string
    {
        return $this->context;
    }
}
