<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\SharedContext\Domain\Ports\Inbound\Command;

final readonly class LogoutAUser implements Command
{
    public function __construct(
        private string $refreshToken,
    ) {
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }
}
