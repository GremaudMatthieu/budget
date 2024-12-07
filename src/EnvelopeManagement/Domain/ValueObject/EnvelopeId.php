<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Domain\ValueObject;

use Assert\Assertion;
use Assert\AssertionFailedException;

readonly class EnvelopeId
{
    /**
     * @throws AssertionFailedException
     */
    private function __construct(protected string $uuid)
    {
        Assertion::uuid($uuid);
    }

    /**
     * @throws AssertionFailedException
     */
    public static function create(string $uuid): self
    {
        return new self($uuid);
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
