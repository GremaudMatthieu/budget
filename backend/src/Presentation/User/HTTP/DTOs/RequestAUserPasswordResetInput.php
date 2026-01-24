<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RequestAUserPasswordResetInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email(message: 'users.emailInvalid')]
        private(set) string $email,
    ) {
    }
}
