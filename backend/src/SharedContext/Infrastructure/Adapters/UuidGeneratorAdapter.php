<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Adapters;

use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class UuidGeneratorAdapter implements UuidGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
