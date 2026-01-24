<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Adapters;

use App\SharedContext\Domain\Ports\Outbound\Translator as TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface as Translator;

final readonly class TranslatorAdapter implements TranslatorInterface
{
    public function __construct(private Translator $translator)
    {
    }

    #[\Override]
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}
