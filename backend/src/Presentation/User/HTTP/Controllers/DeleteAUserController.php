<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\Controllers;

use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use App\UserContext\Application\Commands\DeleteAUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/delete', name: 'app_user_delete', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class DeleteAUserController
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
    ): JsonResponse {
        $this->commandBus->execute(new DeleteAUser(UserId::fromString($currentUser->getUuid())));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
