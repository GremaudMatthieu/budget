<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Builders;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\Aggregates\UserEmailRegistry;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserEmailRegistryId;

final class UserEmailRegistryBuilder
{
    private ?UserEmailRegistry $registry = null;
    public int $registryVersion = 0;

    private function __construct(private readonly EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public static function build(
        EventSourcedRepositoryInterface $eventSourcedRepository,
    ): self {
        return new self($eventSourcedRepository);
    }

    public function loadOrCreateRegistry(): self
    {
        $registryId = UserEmailRegistryId::fromString(UserEmailRegistry::DEFAULT_ID);

        try {
            /** @var UserEmailRegistry $registry */
            $registry = $this->eventSourcedRepository->get((string) $registryId);
            $this->registry = $registry;
            $this->registryVersion = $registry->aggregateVersion();
        } catch (EventsNotFoundForAggregateException) {
            $this->registry = UserEmailRegistry::create($registryId);
            $this->registryVersion = 0;
        }

        return $this;
    }

    public function ensureEmailIsAvailable(UserEmail $email, ?UserId $currentUserId = null): self
    {
        if ($this->registry === null) {
            $this->loadOrCreateRegistry();
        }

        if ($this->registry->isEmailRegistered($email)) {
            $owner = $this->registry->getEmailOwner($email);
            if ($currentUserId === null || $owner !== (string) $currentUserId) {
                throw new UserAlreadyExistsException();
            }
        }

        return $this;
    }

    public function registerEmail(UserEmail $email, UserId $userId): self
    {
        if ($this->registry === null) {
            $this->loadOrCreateRegistry();
        }

        $this->registry->registerEmail($email, $userId);

        return $this;
    }

    public function releaseEmail(UserEmail $email, UserId $userId): self
    {
        if ($this->registry === null) {
            $this->loadOrCreateRegistry();
        }

        $this->registry->releaseEmail($email, $userId);

        return $this;
    }

    public function getRegistryAggregate(): ?UserEmailRegistry
    {
        if ($this->registry !== null && count($this->registry->raisedDomainEvents()) > 0) {
            return $this->registry;
        }

        return null;
    }
}
