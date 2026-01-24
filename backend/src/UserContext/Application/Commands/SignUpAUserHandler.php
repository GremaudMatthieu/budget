<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregate;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;
use App\UserContext\Domain\Exceptions\UserAlreadyExists;
use App\UserContext\Domain\User;
use App\UserContext\Domain\UserEmailRegistryBuilder;

final readonly class SignUpAUserHandler
{
    public function __construct(
        private EventSourcedRepository $eventSourcedRepository,
    ) {
    }

    public function __invoke(SignUpAUser $signUpAUserCommand): void
    {
        try {
            if ($this->eventSourcedRepository->get((string) $signUpAUserCommand->getUserId()) instanceof User) {
                throw new UserAlreadyExists();
            }
        } catch (EventsNotFoundForAggregate) {
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
                $signUpAUserCommand->getContext(),
            );
            $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
        }
    }
}
