<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Aggregates;

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\UserContext\Domain\Events\UserEmailRegisteredDomainEvent;
use App\UserContext\Domain\Events\UserEmailReleasedDomainEvent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserEmailRegistryId;
use App\UserContext\Domain\ValueObjects\UserId;

final class UserEmailRegistry implements AggregateRootInterface
{
    private string $userEmailRegistryId;
    private int $aggregateVersion = 0;
    private array $raisedDomainEvents = [];
    private array $emailHashes = [];

    public const string DEFAULT_ID = 'bd8b9904-2ea3-4b95-9381-4dd7e4db94dd';

    private function __construct()
    {
    }

    public static function create(UserEmailRegistryId $registryId): self
    {
        $registry = new self();
        $registry->userEmailRegistryId = (string) $registryId;
        return $registry;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function registerEmail(UserEmail $email, UserId $userId): void
    {
        $emailHash = $this->hashEmail((string) $email);
        $this->raiseDomainEvent(
            new UserEmailRegisteredDomainEvent(
                $this->userEmailRegistryId,
                (string)$userId,
                $emailHash,
            ),
        );
    }

    public function releaseEmail(UserEmail $email, UserId $userId): void
    {
        $emailHash = $this->hashEmail((string) $email);
        $this->raiseDomainEvent(
            new UserEmailReleasedDomainEvent(
                $this->userEmailRegistryId,
                (string)$userId,
                $emailHash,
            ),
        );
    }

    public function isEmailRegistered(UserEmail $email): bool
    {
        $emailHash = $this->hashEmail((string) $email);

        return isset($this->emailHashes[$emailHash]) && $this->emailHashes[$emailHash]['isRegistered'];
    }

    public function getEmailOwner(UserEmail $email): ?string
    {
        $emailHash = $this->hashEmail((string) $email);

        return isset($this->emailHashes[$emailHash]) && $this->emailHashes[$emailHash]['isRegistered']
            ? $this->emailHashes[$emailHash]['userId']
            : null;
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function setAggregateVersion(int $aggregateVersion): self
    {
        $this->aggregateVersion = $aggregateVersion;
        return $this;
    }

    public function raisedDomainEvents(): array
    {
        return $this->raisedDomainEvents;
    }

    public function getAggregateId(): string
    {
        return $this->userEmailRegistryId;
    }

    public function applyUserEmailRegisteredDomainEvent(UserEmailRegisteredDomainEvent $event): void
    {
        $this->userEmailRegistryId = $event->aggregateId;
        $this->emailHashes[$event->email] = [
            'isRegistered' => true,
            'userId' => $event->userId
        ];
    }

    public function applyUserEmailReleasedDomainEvent(UserEmailReleasedDomainEvent $event): void
    {
        $this->userEmailRegistryId = $event->aggregateId;
        if (isset($this->emailHashes[$event->email])) {
            $this->emailHashes[$event->email]['isRegistered'] = false;
        }
    }

    public function clearRaisedDomainEvents(): void
    {
        $this->raisedDomainEvents = [];
    }

    private function raiseDomainEvent(object $event): void
    {
        $this->raisedDomainEvents[] = $event;
    }

    private function hashEmail(string $email): string
    {
        return hash('sha256', strtolower($email));
    }
}
