<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ResetAUserPasswordCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\UserNotFoundException;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserPassword;

final readonly class ResetAUserPasswordCommandHandler
{
    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ResetAUserPasswordCommand $command): void
    {
        $userView = $this->userViewRepository->findOneBy(
            [
                'passwordResetToken' => (string) $command->getUserPasswordResetToken(),
            ],
        );

        if (!$userView instanceof UserViewInterface) {
            throw new UserNotFoundException();
        }

        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get($userView->getUuid());
        $aggregate->resetPassword(
            UserPassword::fromString(
                $this->passwordHasher->hash($userView, (string) $command->getUserNewPassword()),
            ),
            UserId::fromString($userView->getUuid()),
        );
    }
}
