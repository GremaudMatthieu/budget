<?php

namespace App\UserContext\Domain\Aggregates;

use App\Libraries\FluxCapacitor\Anonymizer\Traits\UserDomainEventsCapabilityTrait;
use App\Libraries\FluxCapacitor\Anonymizer\Traits\EncryptedKeyCacheTrait;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\UserAggregateInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;

final class User implements AggregateRootInterface, UserAggregateInterface
{
    use UserDomainEventsCapabilityTrait;
    use EncryptedKeyCacheTrait;

    private UserId $userId;
    private UserEmail $email;
    private UserFirstname $firstname;
    private UserLastname $lastname;
    private UserLanguagePreference $languagePreference;
    private UserConsent $consentGiven;
    private \DateTimeImmutable $consentDate;
    private \DateTimeImmutable $createdAt;
    private \DateTime $updatedAt;
    private array $roles = ['ROLE_USER'];
    private int $aggregateVersion = 0;
    private UserRegistrationContext $userRegistrationContext;
    private string $providerUserId;

    private function __construct()
    {
    }

    public static function create(
        UserId $userId,
        UserEmail $email,
        UserFirstname $firstname,
        UserLastname $lastname,
        UserLanguagePreference $languagePreference,
        UserConsent $isConsentGiven,
        UserRegistrationContext $registrationContext,
        string $providerUserId,
    ): self {
        $aggregate = new self();
        $aggregate->raiseDomainEvent(
            new UserSignedUpDomainEvent(
                (string) $userId,
                (string) $email,
                (string) $firstname,
                (string) $lastname,
                (string) $languagePreference,
                $isConsentGiven->toBool(),
                $aggregate->roles,
                (string) $userId,
                (string) $registrationContext,
                $providerUserId,
            ),
        );

        return $aggregate;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function updateFirstname(UserFirstname $firstname, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new UserFirstnameChangedDomainEvent(
                (string) $this->userId,
                (string) $firstname,
                (string) $this->userId,
            ),
        );
    }

    public function updateLanguagePreference(UserLanguagePreference $languagePreference, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new UserLanguagePreferenceChangedDomainEvent(
                (string) $this->userId,
                (string) $languagePreference,
                (string) $this->userId,
            ),
        );
    }

    public function updateLastname(UserLastname $lastname, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new UserLastnameChangedDomainEvent(
                (string) $this->userId,
                (string) $lastname,
                (string) $this->userId,
            ),
        );
    }

    public function delete(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new UserDeletedDomainEvent(
                (string) $this->userId,
                (string) $this->userId,
            ),
        );
    }

    public function rewind(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new UserRewoundDomainEvent(
                (string) $this->userId,
                (string) $this->firstname,
                (string) $this->lastname,
                (string) $this->languagePreference,
                (string) $this->email,
                $this->consentGiven->toBool(),
                UtcClock::fromImmutableToString($this->consentDate),
                UtcClock::fromDateTimeToString($this->updatedAt),
                (string) $this->userId,
                (string) $this->userRegistrationContext,
                $this->providerUserId,
            ),
        );
    }

    public function replay(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new UserReplayedDomainEvent(
                (string) $this->userId,
                (string) $this->firstname,
                (string) $this->lastname,
                (string) $this->languagePreference,
                (string) $this->email,
                $this->consentGiven->toBool(),
                UtcClock::fromImmutableToString($this->consentDate),
                UtcClock::fromDateTimeToString($this->updatedAt),
                (string) $this->userId,
                (string) $this->userRegistrationContext,
                $this->providerUserId,
            ),
        );
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

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

    public function getAggregateId(): string
    {
        return (string) $this->userId;
    }

    public function applyUserSignedUpDomainEvent(UserSignedUpDomainEvent $event): void
    {
        $this->userId = UserId::fromString($event->aggregateId);
        $this->email = UserEmail::fromString($event->email);
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->languagePreference = UserLanguagePreference::fromString($event->languagePreference);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
        $this->createdAt = $event->occurredOn;
        $this->consentGiven = UserConsent::fromBool($event->isConsentGiven);
        $this->consentDate = UtcClock::immutableNow();
        $this->roles = ['ROLE_USER'];
        $this->userRegistrationContext = UserRegistrationContext::fromString($event->registrationContext);
        $this->providerUserId = $event->providerUserId;
    }

    public function applyUserFirstnameChangedDomainEvent(UserFirstnameChangedDomainEvent $event): void
    {
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyUserLanguagePreferenceChangedDomainEvent(UserLanguagePreferenceChangedDomainEvent $event): void
    {
        $this->languagePreference = UserLanguagePreference::fromString($event->languagePreference);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyUserLastnameChangedDomainEvent(UserLastnameChangedDomainEvent $event): void
    {
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyUserDeletedDomainEvent(): void
    {
        $this->updatedAt = UtcClock::now();
    }

    public function applyUserReplayedDomainEvent(UserReplayedDomainEvent $event): void
    {
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->languagePreference = UserLanguagePreference::fromString($event->languagePreference);
        $this->email = UserEmail::fromString($event->email);
        $this->consentGiven = UserConsent::fromBool($event->isConsentGiven);
        $this->consentDate = UtcClock::fromDateTimeImmutable($event->consentDate);
        $this->updatedAt = UtcClock::fromDatetime($event->updatedAt);
        $this->userRegistrationContext = UserRegistrationContext::fromString($event->registrationContext);
        $this->providerUserId = $event->providerUserId;
    }

    public function applyUserRewoundDomainEvent(UserRewoundDomainEvent $event): void
    {
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->languagePreference = UserLanguagePreference::fromString($event->languagePreference);
        $this->email = UserEmail::fromString($event->email);
        $this->consentGiven = UserConsent::fromBool($event->isConsentGiven);
        $this->consentDate = UtcClock::fromDateTimeImmutable($event->consentDate);
        $this->updatedAt = UtcClock::fromDatetime($event->updatedAt);
        $this->userRegistrationContext = UserRegistrationContext::fromString($event->registrationContext);
        $this->providerUserId = $event->providerUserId;
    }

    private function assertOwnership(UserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw new UserIsNotOwnedByUserException('users.notOwner');
        }
    }
}
