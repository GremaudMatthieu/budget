<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\UserContext\Domain\Ports\Outbound\JWTTokenManagerInterface;
use App\UserContext\ReadModels\Views\UserView;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface as LexikJWTTokenManagerInterface;

final readonly class JWTTokenManagerAdapter implements JWTTokenManagerInterface
{
    public function __construct(private LexikJWTTokenManagerInterface $JWTTokenManager)
    {
    }

    #[\Override]
    public function create(UserView $userView): string
    {
        return $this->JWTTokenManager->create($userView);
    }
}
