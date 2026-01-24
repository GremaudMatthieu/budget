<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SignUpAUserInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[Assert\Regex(
            pattern: '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        )]
        private(set) string $uuid,
        #[Assert\NotBlank]
        #[Assert\Email(message: 'users.emailInvalid')]
        private(set) string $email,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 8,
            max: 50,
            minMessage: 'users.passwordMinLength',
            maxMessage: 'users.passwordMaxLength',
        )]
        #[Assert\PasswordStrength]
        private(set) string $password,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.firstnameMinLength',
            maxMessage: 'users.firstnameMaxLength',
        )]
        private(set) string $firstname,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.lastnameMinLength',
            maxMessage: 'users.lastnameMaxLength',
        )]
        private(set) string $lastname,
        #[Assert\NotBlank]
        #[Assert\Locale(message: 'users.languagePreferenceInvalid')]
        private(set) string $languagePreference,
        #[Assert\NotNull]
        #[Assert\IsTrue(message: 'users.consentNotGiven')]
        #[Assert\Type(type: 'bool')]
        private(set) bool $consentGiven,
    ) {
    }
}
