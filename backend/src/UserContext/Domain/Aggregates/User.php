<?php

namespace App\UserContext\Domain\Aggregates;

use App\Libraries\FluxCapacitor\Anonymizer\Traits\EncryptedKeyCacheTrait;
use App\Libraries\FluxCapacitor\Anonymizer\Traits\UserDomainEventsCapabilityTrait;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\SnapshotableAggregateInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\UserAggregateInterface;
use App\SharedContext\Domain\ValueObjects\Context;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use App\UserContext\Domain\Events\UserDeletedDomainEvent_v1;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserReplayedDomainEvent_v1;
use App\UserContext\Domain\Events\UserRewoundDomainEvent_v1;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent_v1;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;

final class User implements AggregateRootInterface, UserAggregateInterface, SnapshotableAggregateInterface
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
    private Context $context;

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
        Context $context,
    ): self {
        $aggregate = new self();
        $aggregate->raiseDomainEvent(
            new UserSignedUpDomainEvent_v1(
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
                $context->getContextId(),
                $context->getContext(),
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
            new UserFirstnameChangedDomainEvent_v1(
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
            new UserLanguagePreferenceChangedDomainEvent_v1(
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
            new UserLastnameChangedDomainEvent_v1(
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
            new UserDeletedDomainEvent_v1(
                (string) $this->userId,
                (string) $this->userId,
            ),
        );
    }

    public function rewind(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvent(
            new UserRewoundDomainEvent_v1(
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
            new UserReplayedDomainEvent_v1(
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

    public function applyUserSignedUpDomainEvent_v1(UserSignedUpDomainEvent_v1 $event): void
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
        $this->context = Context::from($event->contextId, $event->context);
    }

    public function applyUserFirstnameChangedDomainEvent_v1(UserFirstnameChangedDomainEvent_v1 $event): void
    {
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyUserLanguagePreferenceChangedDomainEvent_v1(UserLanguagePreferenceChangedDomainEvent_v1 $event): void
    {
        $this->languagePreference = UserLanguagePreference::fromString($event->languagePreference);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyUserLastnameChangedDomainEvent_v1(UserLastnameChangedDomainEvent_v1 $event): void
    {
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyUserDeletedDomainEvent_v1(): void
    {
        $this->updatedAt = UtcClock::now();
    }

    public function applyUserReplayedDomainEvent_v1(UserReplayedDomainEvent_v1 $event): void
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

    public function applyUserRewoundDomainEvent_v1(UserRewoundDomainEvent_v1 $event): void
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

    public function createSnapshot(): array
    {
        return [
            'userId' => (string) $this->userId,
            'email' => (string) $this->email,
            'firstname' => (string) $this->firstname,
            'lastname' => (string) $this->lastname,
            'languagePreference' => (string) $this->languagePreference,
            'consentGiven' => $this->consentGiven->toBool(),
            'consentDate' => $this->consentDate->format(\DateTime::ATOM),
            'createdAt' => $this->createdAt->format(\DateTime::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTime::ATOM),
            'roles' => $this->roles,
            'userRegistrationContext' => (string) $this->userRegistrationContext,
            'providerUserId' => $this->providerUserId,
            'context' => [
                'contextId' => $this->context->getContextId(),
                'context' => $this->context->getContext(),
            ],
        ];
    }

    public static function fromSnapshot(array $data, int $version): self
    {
        $user = new self();

        $user->userId = UserId::fromString($data['userId']);
        $user->email = UserEmail::fromString($data['email']);
        $user->firstname = UserFirstname::fromString($data['firstname']);
        $user->lastname = UserLastname::fromString($data['lastname']);
        $user->languagePreference = UserLanguagePreference::fromString($data['languagePreference']);
        $user->consentGiven = UserConsent::fromBool($data['consentGiven']);
        $user->consentDate = new \DateTimeImmutable($data['consentDate']);
        $user->createdAt = new \DateTimeImmutable($data['createdAt']);
        $user->updatedAt = new \DateTime($data['updatedAt']);
        $user->roles = $data['roles'];
        $user->userRegistrationContext = UserRegistrationContext::fromString($data['userRegistrationContext']);
        $user->providerUserId = $data['providerUserId'];
        $user->context = Context::from($data['context']['contextId'], $data['context']['context']);
        $user->aggregateVersion = $version;

        return $user;
    }
}
