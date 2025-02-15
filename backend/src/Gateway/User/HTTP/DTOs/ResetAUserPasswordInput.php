<?php

declare(strict_types=1);

namespace App\Gateway\User\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ResetAUserPasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        private(set) string $token,
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
