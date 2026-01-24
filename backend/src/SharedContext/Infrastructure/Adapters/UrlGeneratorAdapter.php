<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Adapters;

use App\SharedContext\Domain\Ports\Outbound\UrlGenerator as CustomUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;

final readonly class UrlGeneratorAdapter implements CustomUrlGenerator
{
    public function __construct(private SymfonyUrlGenerator $urlGenerator)
    {
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[\Override]
    public function generate(string $route, array $parameters = [], int $referenceType = SymfonyUrlGenerator::ABSOLUTE_PATH): string
    {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }
}
