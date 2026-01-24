<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use App\UserContext\Infrastructure\Entities\UserOAuth;

interface UserOAuthRepository
{
    public function findOneBy(array $criteria): ?UserOAuth;

    public function createOAuthLink(string $userId, string $provider, string $providerUserId): void;

    public function removeOAuthUser(string $userId): void;
}
