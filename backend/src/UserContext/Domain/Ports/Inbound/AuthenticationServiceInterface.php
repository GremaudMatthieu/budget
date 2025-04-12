<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

use App\UserContext\ReadModels\Views\UserView;

interface AuthenticationServiceInterface
{
    public function generateTokensForUser(UserView $user): array;

    public function authenticateByEmail(string $email): array;
}
