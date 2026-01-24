<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use Assert\Assert;

final readonly class UserLastname
{
    private function __construct(protected string $name)
    {
        Assert::that($name)
            ->notBlank('Lastname should not be blank.')
            ->minLength(2, 'The last name must be at least 2 characters long.')
            ->maxLength(255, 'The last name cannot be longer than 255 characters.')
        ;
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
