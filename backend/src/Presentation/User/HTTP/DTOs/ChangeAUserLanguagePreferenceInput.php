<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeAUserLanguagePreferenceInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Locale(message: 'users.languagePreferenceInvalid')]
        private(set) string $languagePreference,
    ) {
    }
}
