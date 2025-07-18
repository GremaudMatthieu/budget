<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

use App\Gateway\User\Views\UserView;

interface AuthenticationServiceInterface
{
    public function generateTokensForUser(UserView $user): array;

    public function authenticateByEmail(string $email): array;
}
