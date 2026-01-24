<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

interface UrlGenerator
{
    public const ABSOLUTE_URL = 0;

    /**
     * @param array<string, mixed> $parameters
     */
    public function generate(string $route, array $parameters, int $referenceType): string;
}
