<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use Assert\Assert;

final readonly class UserEmail
{
    private function __construct(protected string $email)
    {
        Assert::that($email)
            ->notBlank('Email should not be blank.')
            ->email('The email "{{ value }}" is not a valid email.')
        ;
    }

    public static function fromString(string $email): self
    {
        return new self($email);
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
