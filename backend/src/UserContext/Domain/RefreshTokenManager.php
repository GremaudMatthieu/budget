<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;

interface RefreshTokenManager
{
    public function get(string $refreshToken): ?RefreshTokenInterface;

    public function deleteAll(string $userEmail): void;
}
