<?php

declare(strict_types=1);

namespace App\Gateway\User\Presentation\HTTP\Controllers;

use App\Gateway\User\Presentation\HTTP\DTOs\ChangeAUserLanguagePreferenceInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Application\Commands\ChangeAUserLanguagePreferenceCommand;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/language-preference', name: 'app_user_change_language_preference', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class ChangeAUserLanguagePreferenceController
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
        #[MapRequestPayload] ChangeAUserLanguagePreferenceInput $changeAUserLanguagePreferenceInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeAUserLanguagePreferenceCommand(
                UserId::fromString($currentUser->getUuid()),
                UserLanguagePreference::fromString($changeAUserLanguagePreferenceInput->languagePreference),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
