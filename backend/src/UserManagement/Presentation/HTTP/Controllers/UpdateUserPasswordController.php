<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\UpdateUserPasswordCommand;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Presentation\HTTP\DTOs\UpdateUserPasswordInput;
use App\UserManagement\ReadModels\Views\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/change-password', name: 'app_user_change_password', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class UpdateUserPasswordController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserView $currentUser,
        #[MapRequestPayload] UpdateUserPasswordInput $updateUserPasswordInput,
    ): JsonResponse {
        $this->commandBus->execute(new UpdateUserPasswordCommand(
            $updateUserPasswordInput->getOldPassword(),
            $updateUserPasswordInput->getNewPassword(),
            $currentUser->getUuid(),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}