<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

use App\UserContext\Domain\Entities\UserOAuth;

interface UserOAuthRepositoryInterface
{
    public function findOneBy(array $criteria): ?UserOAuth;
    
    public function createOAuthLink(string $userId, string $provider, string $providerUserId): void;

    public function removeOAuthUser(string $userId): void;
}