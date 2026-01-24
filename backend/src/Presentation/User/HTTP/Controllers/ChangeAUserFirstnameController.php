<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\Controllers;

use App\Presentation\User\HTTP\DTOs\ChangeAUserFirstnameInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\UserView;
use App\UserContext\Application\Commands\ChangeAUserFirstname;
use App\UserContext\Domain\UserFirstname;
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
        private CommandBus $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserView $currentUser,
        #[MapRequestPayload] ChangeAUserFirstnameInput $changeAUserFirstnameInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeAUserFirstname(
                UserId::fromString($currentUser->getUuid()),
                UserFirstname::fromString($changeAUserFirstnameInput->firstname),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
