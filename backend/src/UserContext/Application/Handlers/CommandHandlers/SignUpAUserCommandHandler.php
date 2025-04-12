<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\SignUpAUserCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Builders\UserEmailRegistryBuilder;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;

final readonly class SignUpAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(SignUpAUserCommand $signUpAUserCommand): void
    {
        try {
            if ($this->eventSourcedRepository->get((string) $signUpAUserCommand->getUserId()) instanceof User) {
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
            $aggregatesToSave[] = User::create(
                $signUpAUserCommand->getUserId(),
                $signUpAUserCommand->getUserEmail(),
                $signUpAUserCommand->getUserFirstname(),
                $signUpAUserCommand->getUserLastname(),
                $signUpAUserCommand->getUserLanguagePreference(),
                $signUpAUserCommand->isUserConsentGiven(),
                $signUpAUserCommand->getUserRegistrationContext(),
                $signUpAUserCommand->getProviderUserId(),
            );
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
