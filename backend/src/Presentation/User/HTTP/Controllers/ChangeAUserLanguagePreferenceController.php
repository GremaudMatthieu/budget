<?php

declare(strict_types=1);

namespace App\Presentation\User\HTTP\Controllers;

use App\Presentation\User\HTTP\DTOs\ChangeAUserLanguagePreferenceInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\UserView;
use App\UserContext\Application\Commands\ChangeAUserLanguagePreference;
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
        private CommandBus $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserView $currentUser,
        #[MapRequestPayload] ChangeAUserLanguagePreferenceInput $changeAUserLanguagePreferenceInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new ChangeAUserLanguagePreference(
                UserId::fromString($currentUser->getUuid()),
                UserLanguagePreference::fromString($changeAUserLanguagePreferenceInput->languagePreference),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
