<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeAUserLastnameInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.lastnameMinLength',
            maxMessage: 'users.lastnameMaxLength',
        )]
        private(set) string $lastname,
    ) {
    }
}
