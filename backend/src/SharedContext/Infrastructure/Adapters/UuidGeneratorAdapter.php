<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Adapters;

use App\SharedContext\Domain\Ports\Outbound\UuidGenerator;
use Symfony\Component\Uid\Uuid;

final class UuidGeneratorAdapter implements UuidGenerator
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }

    public static function uuidV5(string $namespace, string $name): string
    {
        return Uuid::v5(Uuid::fromString($namespace), $name)->toRfc4122();
    }
}
