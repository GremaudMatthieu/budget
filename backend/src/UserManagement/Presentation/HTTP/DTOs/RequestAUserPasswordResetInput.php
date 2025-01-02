<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RequestAUserPasswordResetInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email(message: 'users.emailInvalid')]
        private string $email,
    ) {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
