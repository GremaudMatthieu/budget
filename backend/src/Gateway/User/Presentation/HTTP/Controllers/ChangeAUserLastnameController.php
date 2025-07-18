<?php

declare(strict_types=1);

namespace App\Gateway\User\Presentation\HTTP\Controllers;

use App\Gateway\User\Presentation\HTTP\DTOs\ChangeAUserLastnameInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Application\Commands\ChangeAUserLastnameCommand;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\ValueObjects\UserLastname;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/lastname', name: 'app_user_change_lastname', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class ChangeAUserLastnameController
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
        #[MapRequestPayload] ChangeAUserLastnameInput $changeAUserLastnameInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeAUserLastnameCommand(
                UserId::fromString($currentUser->getUuid()),
                UserLastname::fromString($changeAUserLastnameInput->lastname),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
