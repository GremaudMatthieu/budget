<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Application\Commands\SignUpAUserCommand;
use App\UserContext\Application\Commands\SignUpOrAuthenticateWithOAuth2Command;
use App\UserContext\Domain\Ports\Inbound\UserOAuthRepositoryInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\ReadModels\Views\UserView;

final readonly class SignUpOrAuthenticateWithOAuth2CommandHandler
{
    public function __construct(
        private UserOAuthRepositoryInterface $userOAuthRepository,
        private UserViewRepositoryInterface $userViewRepository,
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(SignUpOrAuthenticateWithOAuth2Command $command): void
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
            new SignUpAUserCommand(
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
