<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeAUserPasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 8,
            max: 50,
            minMessage: 'users.passwordMinLength',
            maxMessage: 'users.passwordMaxLength',
        )]
        private(set) string $oldPassword,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 8,
            max: 50,
            minMessage: 'users.passwordMinLength',
            maxMessage: 'users.passwordMaxLength',
        )]
        #[Assert\PasswordStrength]
        private(set) string $newPassword,
    ) {
    }
}
