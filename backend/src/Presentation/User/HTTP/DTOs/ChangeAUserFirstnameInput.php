<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\DTOs;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ChangeAUserFirstnameInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(
            min: 2,
            max: 50,
            minMessage: 'users.firstnameMinLength',
            maxMessage: 'users.firstnameMaxLength',
        )]
        private(set) string $firstname,
    ) {
    }
}
