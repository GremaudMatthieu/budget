<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\UserContext\Domain\Ports\Outbound\RefreshTokenGeneratorInterface;
use App\UserContext\ReadModels\Views\UserView;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface as GesdinetRefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;

final readonly class RefreshTokenGeneratorAdapter implements RefreshTokenGeneratorInterface
{
    public function __construct(private GesdinetRefreshTokenGeneratorInterface $refreshTokenGenerator)
    {
    }

    #[\Override]
    public function createForUserWithTtl(UserView $userView, int $ttl): RefreshTokenInterface
    {
        return $this->refreshTokenGenerator->createForUserWithTtl($userView, $ttl);
    }
}
