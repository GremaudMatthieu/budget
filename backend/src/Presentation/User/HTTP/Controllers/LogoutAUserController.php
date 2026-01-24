<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\Controllers;

use App\Presentation\User\HTTP\DTOs\LogoutAUserInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\UserView;
use App\UserContext\Application\Commands\LogoutAUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/logout', name: 'app_user_logout', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class LogoutAUserController
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserView $currentUser,
        #[MapRequestPayload] LogoutAUserInput $logoutAUserInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new LogoutAUser(
                $logoutAUserInput->refreshToken,
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
