<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\UpdateUserFirstnameCommand;
use App\UserManagement\Domain\Ports\Inbound\UserViewInterface;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Presentation\HTTP\DTOs\UpdateUserFirstnameInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/firstname', name: 'app_user_edit_firstname', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class UpdateUserFirstnameController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserViewInterface $currentUser,
        #[MapRequestPayload] UpdateUserFirstnameInput $updateUserFirstnameInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new UpdateUserFirstnameCommand(
                $currentUser->getUuid(),
                $updateUserFirstnameInput->getFirstname(),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
