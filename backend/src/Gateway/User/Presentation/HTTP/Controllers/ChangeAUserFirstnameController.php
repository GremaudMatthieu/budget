<?php

declare(strict_types=1);

namespace App\Gateway\User\Presentation\HTTP\Controllers;

use App\Gateway\User\Presentation\HTTP\DTOs\ChangeAUserFirstnameInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Application\Commands\ChangeAUserFirstnameCommand;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/firstname', name: 'app_user_change_firstname', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class ChangeAUserFirstnameController
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
        #[MapRequestPayload] ChangeAUserFirstnameInput $changeAUserFirstnameInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeAUserFirstnameCommand(
                UserId::fromString($currentUser->getUuid()),
                UserFirstname::fromString($changeAUserFirstnameInput->firstname),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
