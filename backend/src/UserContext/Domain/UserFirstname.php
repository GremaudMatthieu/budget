<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use Assert\Assert;

final readonly class UserFirstname
{
    private function __construct(protected string $name)
    {
        Assert::that($name)
            ->notBlank('Firstname should not be blank.')
            ->minLength(2, 'The first name must be at least 2 characters long.')
            ->maxLength(255, 'The first name cannot be longer than 255 characters.')
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
