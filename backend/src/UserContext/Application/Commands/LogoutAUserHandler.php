<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\UserContext\Domain\EntityManager;
use App\UserContext\Domain\RefreshTokenManager;

final readonly class LogoutAUserHandler
{
    public function __construct(
        private RefreshTokenManager $refreshTokenManager,
        private EntityManager $entityManager,
    ) {
    }

    public function __invoke(LogoutAUser $logoutAUser): void
    {
        $refreshToken = $this->refreshTokenManager->get($logoutAUser->getRefreshToken());

        if (null === $refreshToken) {
            return;
        }

        $this->entityManager->remove($refreshToken);
        $this->entityManager->flush();
    }
}
