<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\Presentation\User\UserView;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\UserConsent;
use App\UserContext\Domain\UserOAuthRepository;
use App\UserContext\UserViewRepository;

final readonly class SignUpOrAuthenticateWithOAuth2Handler
{
    public function __construct(
        private UserOAuthRepository $userOAuthRepository,
        private UserViewRepository $userViewRepository,
        private CommandBus $commandBus,
    ) {
    }

    public function __invoke(SignUpOrAuthenticateWithOAuth2 $command): void
    {
        if (
            $this->userOAuthRepository->findOneBy(
                [
                    'provider' => (string) $command->getUserRegistrationContext(),
                    'providerUserId' => $command->getProviderUserId(),
                ],
            )
        ) {
            return;
        }

        $existingUserByEmail = $this->userViewRepository->findOneBy(['email' => (string) $command->getUserEmail()]);

        if ($existingUserByEmail) {
            $this->userOAuthRepository->createOAuthLink(
                $existingUserByEmail->getUuid(),
                (string) $command->getUserRegistrationContext(),
                $command->getProviderUserId()
            );

            return;
        }

        $userId = (string) $command->getUserId();
        $this->userOAuthRepository->createOAuthLink(
            $userId,
            (string) $command->getUserRegistrationContext(),
            $command->getProviderUserId(),
        );
        $context = $command->getContext();
        $this->userViewRepository->save(
            UserView::fromOAuth(
                UserId::fromString($userId),
                $command->getUserEmail(),
                $command->getUserFirstname(),
                $command->getUserLastname(),
                $command->getUserLanguagePreference(),
                UserConsent::fromBool(true),
                $command->getUserRegistrationContext(),
                $command->getProviderUserId(),
                $context->getContextId(),
                $context->getContext(),
            ),
        );
        $this->commandBus->execute(
            new SignUpAUser(
                UserId::fromString($userId),
                $command->getUserEmail(),
                $command->getUserFirstname(),
                $command->getUserLastname(),
                $command->getUserLanguagePreference(),
                UserConsent::fromBool(true),
                $command->getUserRegistrationContext(),
                $command->getProviderUserId(),
                $context,
            ),
        );
    }
}
