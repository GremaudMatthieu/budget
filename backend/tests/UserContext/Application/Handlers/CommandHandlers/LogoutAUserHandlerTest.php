<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Presentation\User\HTTP\DTOs\LogoutAUserInput;
use App\UserContext\Application\Commands\LogoutAUser;
use App\UserContext\Application\Commands\LogoutAUserHandler;
use App\UserContext\Domain\EntityManager;
use App\UserContext\Domain\RefreshTokenManager;
use App\UserContext\Infrastructure\Entities\RefreshToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogoutAUserHandlerTest extends TestCase
{
    private RefreshTokenManager&MockObject $refreshTokenManager;
    private EntityManager&MockObject $entityManager;
    private LogoutAUserHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->refreshTokenManager = $this->createMock(RefreshTokenManager::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->handler = new LogoutAUserHandler(
            $this->refreshTokenManager,
            $this->entityManager,
        );
    }

    public function testLogoutUserSuccess(): void
    {
        $logoutUserInput = new LogoutAUserInput('refreshToken');
        $command = new LogoutAUser($logoutUserInput->refreshToken);

        $this->refreshTokenManager->method('get')->willReturn(new RefreshToken());
        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $this->handler->__invoke($command);
    }

    public function testLogoutUserWithWrongRefreshToken(): void
    {
        $logoutUserInput = new LogoutAUserInput('refreshToken');
        $command = new LogoutAUser($logoutUserInput->refreshToken);

        $this->refreshTokenManager->method('get')->willReturn(null);
        $this->entityManager->expects($this->never())->method('remove');

        $this->handler->__invoke($command);
    }
}
