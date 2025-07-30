<?php

declare(strict_types=1);

namespace App\Gateway\User\Views;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\ValueObjects\UserId;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent_v1;
use App\UserContext\Domain\Events\UserReplayedDomainEvent_v1;
use App\UserContext\Domain\Events\UserRewoundDomainEvent_v1;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent_v1;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserRegistrationContext;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_view')]
class UserView implements UserViewInterface, UserInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    public int $id;

    #[ORM\Column(name: 'uuid', type: 'string', length: 36, unique: true)]
    public string $uuid;

    #[ORM\Column(name: 'email', type: 'string', length: 320)]
    public string $email;

    #[ORM\Column(name: 'firstname', type: 'string', length: 50)]
    public string $firstname;

    #[ORM\Column(name: 'lastname', type: 'string', length: 50)]
    public string $lastname;

    #[ORM\Column(name: 'language_preference', type: 'string', length: 35)]
    public string $languagePreference;

    #[ORM\Column(name: 'consent_given', type: 'boolean')]
    public bool $consentGiven;

    #[ORM\Column(name: 'consent_date', type: 'datetime_immutable')]
    public \DateTimeImmutable $consentDate;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    public \DateTime $updatedAt;

    #[ORM\Column(name: 'roles', type: 'json')]
    public array $roles = ['ROLE_USER'];

    #[ORM\Column(name: 'registration_context', type: 'string', length: 32)]
    public string $registrationContext;

    #[ORM\Column(name: 'provider_user_id', type: 'string', length: 255)]
    public string $providerUserId;

    #[ORM\Column(name: 'context_uuid', type: 'string', length: 36)]
    public string $contextId;

    #[ORM\Column(name: 'context', type: 'string', length: 36)]
    public string $context;

    public function __construct(
        string $userId,
        string $email,
        string $firstname,
        string $lastname,
        string $languagePreference,
        bool $consentGiven,
        \DateTimeImmutable $consentDate,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        array $roles,
        string $registrationContext,
        string $providerUserId,
        string $contextId,
        string $context,
    ) {
        $this->uuid = $userId;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->languagePreference = $languagePreference;
        $this->consentGiven = $consentGiven;
        $this->consentDate = $consentDate;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->roles = $roles;
        $this->registrationContext = $registrationContext;
        $this->providerUserId = $providerUserId;
        $this->contextId = $contextId;
        $this->context = $context;
    }

    public static function fromRepository(array $user): self
    {
        return new self(
            $user['uuid'],
            $user['email'],
            $user['firstname'],
            $user['lastname'],
            $user['language_preference'],
            (bool) $user['consent_given'],
            new \DateTimeImmutable($user['consent_date']),
            new \DateTimeImmutable($user['created_at']),
            new \DateTime($user['updated_at']),
            json_decode($user['roles'], true),
            $user['registration_context'],
            $user['provider_user_id'],
            $user['context_uuid'],
            $user['context'],
        );
    }

    public static function fromUserSignedUpDomainEvent_v1(UserSignedUpDomainEvent_v1 $event): self
    {
        return new self(
            $event->aggregateId,
            $event->email,
            $event->firstname,
            $event->lastname,
            $event->languagePreference,
            $event->isConsentGiven,
            $event->occurredOn,
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
            $event->roles,
            $event->registrationContext,
            $event->providerUserId,
            $event->contextId,
            $event->context,
        );
    }

    public static function fromOAuth(
        UserId $userId,
        UserEmail $email,
        UserFirstname $firstname,
        UserLastname $lastname,
        UserLanguagePreference $languagePreference,
        UserConsent $consentGiven,
        UserRegistrationContext $registrationContext,
        string $providerUserId,
        string $contextId,
        string $context,
    ): self {
        return new self(
            (string) $userId,
            (string) $email,
            (string) $firstname,
            (string) $lastname,
            (string) $languagePreference,
            $consentGiven->toBool(),
            UtcClock::immutableNow(),
            UtcClock::immutableNow(),
            UtcClock::now(),
            ['ROLE_USER'],
            (string) $registrationContext,
            $providerUserId,
            $contextId,
            $context,
        );
    }

    public function fromEvents(\Generator $events): void
    {
        /** @var array{type: string, payload: string} $event */
        foreach ($events as $event) {
            $this->apply($event['type']::fromArray(json_decode($event['payload'], true)));
        }
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'languagePreference' => $this->languagePreference,
            'email' => $this->email,
        ];
    }

    private function apply(DomainEventInterface $event): void
    {
        match (get_class($event)) {
            UserSignedUpDomainEvent_v1::class => $this->applyUserSignedUpDomainEvent_v1($event),
            UserFirstnameChangedDomainEvent_v1::class => $this->applyUserFirstnameChangedDomainEvent_v1($event),
            UserLastnameChangedDomainEvent_v1::class => $this->applyUserLastnameChangedDomainEvent_v1($event),
            UserLanguagePreferenceChangedDomainEvent_v1::class => $this->applyUserLanguagePreferenceChangedDomainEvent_v1($event),
            UserReplayedDomainEvent_v1::class => $this->applyUserReplayedDomainEvent_v1($event),
            UserRewoundDomainEvent_v1::class => $this->applyUserRewoundDomainEvent_v1($event),
            default => throw new \RuntimeException('users.unknownEvent'),
        };
    }

    private function applyUserSignedUpDomainEvent_v1(UserSignedUpDomainEvent_v1 $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->email = $event->email;
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->languagePreference = $event->languagePreference;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->createdAt = $event->occurredOn;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = UtcClock::immutableNow();
        $this->roles = ['ROLE_USER'];
        $this->registrationContext = $event->registrationContext;
        $this->providerUserId = $event->providerUserId;
        $this->contextId = $event->contextId;
        $this->context = $event->context;
    }

    private function applyUserFirstnameChangedDomainEvent_v1(UserFirstnameChangedDomainEvent_v1 $event): void
    {
        $this->firstname = $event->firstname;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserLastnameChangedDomainEvent_v1(UserLastnameChangedDomainEvent_v1 $event): void
    {
        $this->lastname = $event->lastname;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserLanguagePreferenceChangedDomainEvent_v1(
        UserLanguagePreferenceChangedDomainEvent_v1 $event,
    ): void {
        $this->languagePreference = $event->languagePreference;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserReplayedDomainEvent_v1(UserReplayedDomainEvent_v1 $event): void
    {
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->languagePreference = $event->languagePreference;
        $this->email = $event->email;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
        $this->registrationContext = $event->registrationContext;
        $this->providerUserId = $event->providerUserId;
    }

    private function applyUserRewoundDomainEvent_v1(UserRewoundDomainEvent_v1 $event): void
    {
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->languagePreference = $event->languagePreference;
        $this->email = $event->email;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
        $this->registrationContext = $event->registrationContext;
        $this->providerUserId = $event->providerUserId;
    }
}
