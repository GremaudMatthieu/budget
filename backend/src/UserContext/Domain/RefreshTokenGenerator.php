<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

use App\Presentation\User\UserView;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;

interface RefreshTokenGenerator
{
    public function createForUserWithTtl(UserView $userView, int $ttl): RefreshTokenInterface;
}
