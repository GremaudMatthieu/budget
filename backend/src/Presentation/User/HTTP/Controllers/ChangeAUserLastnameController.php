<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\Controllers;

use App\Presentation\User\HTTP\DTOs\ChangeAUserLastnameInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use App\UserContext\Application\Commands\ChangeAUserLastname;
use App\UserContext\Domain\UserLastname;
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
        private CommandBus $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserView $currentUser,
        #[MapRequestPayload] ChangeAUserLastnameInput $changeAUserLastnameInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeAUserLastname(
                UserId::fromString($currentUser->getUuid()),
                UserLastname::fromString($changeAUserLastnameInput->lastname),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
