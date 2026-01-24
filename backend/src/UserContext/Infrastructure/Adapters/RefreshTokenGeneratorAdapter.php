<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\Presentation\User\UserView;
use App\UserContext\Domain\RefreshTokenGenerator;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGenerator as GesdinetRefreshTokenGenerator;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;

final readonly class RefreshTokenGeneratorAdapter implements RefreshTokenGenerator
{
    public function __construct(private GesdinetRefreshTokenGenerator $refreshTokenGenerator)
    {
    }

    #[\Override]
    public function createForUserWithTtl(UserView $userView, int $ttl): RefreshTokenInterface
    {
        return $this->refreshTokenGenerator->createForUserWithTtl($userView, $ttl);
    }
}
