<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Outbound;

use App\UserContext\ReadModels\Views\UserView;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;

interface RefreshTokenGeneratorInterface
{
    public function createForUserWithTtl(UserView $userView, int $ttl): RefreshTokenInterface;
}
