<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use Assert\Assert;

final readonly class UserConsent
{
    private function __construct(protected bool $consent)
    {
        Assert::that($consent)
            ->notNull('Consent must be provided.')
            ->boolean('The consent must be a boolean value.')
            ->true('Consent must be accepted.')
        ;
    }

    public static function fromBool(bool $consent): self
    {
        return new self($consent);
    }

    public function toBool(): bool
    {
        return $this->consent;
    }
}
