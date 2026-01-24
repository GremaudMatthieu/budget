<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\Presentation\User\UserView;
use App\UserContext\JWTTokenManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManager as LexikJWTTokenManager;

final readonly class JWTTokenManagerAdapter implements JWTTokenManager
{
    public function __construct(private LexikJWTTokenManager $JWTTokenManager)
    {
    }

    #[\Override]
    public function create(UserView $userView): string
    {
        return $this->JWTTokenManager->create($userView);
    }
}
