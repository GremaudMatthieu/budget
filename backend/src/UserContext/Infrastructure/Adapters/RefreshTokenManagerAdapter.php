<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\UserContext\Domain\RefreshTokenManager;
use Doctrine\DBAL\Connection;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManager as JWTRefreshTokenManager;

final readonly class RefreshTokenManagerAdapter implements RefreshTokenManager
{
    public function __construct(
        private JWTRefreshTokenManager $refreshTokenManager,
        private Connection $connection,
    ) {
    }

    #[\Override]
    public function get(string $refreshToken): ?RefreshTokenInterface
    {
        return $this->refreshTokenManager->get($refreshToken);
    }

    #[\Override]
    public function deleteAll(string $userEmail): void
    {
        $this->connection->createQueryBuilder()
            ->delete('refresh_tokens')
            ->where('username = :username')
            ->setParameter('username', $userEmail)
            ->executeStatement();
    }
}
