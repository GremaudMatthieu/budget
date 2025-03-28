<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\SignUpAUserCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Builders\UserEmailRegistryBuilder;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\ReadModels\Views\UserView;

final readonly class SignUpAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private PasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function __invoke(SignUpAUserCommand $signUpAUserCommand): void
    {
        try {
            $aggregate = $this->eventSourcedRepository->get((string)$signUpAUserCommand->getUserId());

            if ($aggregate instanceof User) {
                throw new UserAlreadyExistsException();
            }
        } catch (EventsNotFoundForAggregateException) {
            $aggregatesToSave[] = UserEmailRegistryBuilder::build($this->eventSourcedRepository)
                ->loadOrCreateRegistry()
                ->ensureEmailIsAvailable($signUpAUserCommand->getUserEmail())
                ->registerEmail(
                    $signUpAUserCommand->getUserEmail(),
                    $signUpAUserCommand->getUserId()
                )
                ->getRegistryAggregate()
            ;
            $aggregate = User::create(
                $signUpAUserCommand->getUserId(),
                $signUpAUserCommand->getUserEmail(),
                UserPassword::fromString(
                    $this->userPasswordHasher->hash(
                        new UserView(
                            $signUpAUserCommand->getUserId(),
                            $signUpAUserCommand->getUserEmail(),
                            UserPassword::fromString((string)$signUpAUserCommand->getUserPassword()),
                            $signUpAUserCommand->getUserFirstname(),
                            $signUpAUserCommand->getUserLastname(),
                            $signUpAUserCommand->getUserLanguagePreference(),
                            $signUpAUserCommand->isUserConsentGiven(),
                            new \DateTimeImmutable(),
                            new \DateTimeImmutable(),
                            new \DateTime(),
                            ['ROLE_USER'],
                        ),
                        (string) $signUpAUserCommand->getUserPassword(),
                    ),
                ),
                $signUpAUserCommand->getUserFirstname(),
                $signUpAUserCommand->getUserLastname(),
                $signUpAUserCommand->getUserLanguagePreference(),
                $signUpAUserCommand->isUserConsentGiven(),
            );
            $aggregatesToSave[] = $aggregate;
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
