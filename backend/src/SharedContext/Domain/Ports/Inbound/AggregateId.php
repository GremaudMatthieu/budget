<?php

namespace App\SharedContext\Domain\Ports\Inbound;

interface AggregateId
{
    public static function fromString(string $uuid): self;

    public function __toString(): string;
}
