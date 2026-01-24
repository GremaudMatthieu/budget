<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

interface Translator
{
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string;
}
