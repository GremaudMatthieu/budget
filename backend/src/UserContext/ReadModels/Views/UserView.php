<?php

declare(strict_types=1);

namespace App\UserContext\ReadModels\Views;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Domain\ValueObjects\UtcClock;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_view')]
final class UserView implements UserViewInterface, UserInterface, PasswordAuthenticatedUserInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'user_view_id_seq', allocationSize: 1, initialValue: 1)]
    private(set) int $id;

    #[ORM\Column(name: 'uuid', type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'email', type: 'string', length: 320)]
    private(set) string $email;

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private(set) string $password;

    #[ORM\Column(name: 'firstname', type: 'string', length: 50)]
    private(set) string $firstname;

    #[ORM\Column(name: 'lastname', type: 'string', length: 50)]
    private(set) string $lastname;

    #[ORM\Column(name: 'language_preference', type: 'string', length: 35)]
    private(set) string $languagePreference;

    #[ORM\Column(name: 'consent_given', type: 'boolean')]
    private(set) bool $consentGiven;

    #[ORM\Column(name: 'consent_date', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $consentDate;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    #[ORM\Column(name: 'roles', type: 'json')]
    private(set) array $roles = ['ROLE_USER'];

    #[ORM\Column(name: 'password_reset_token', type: 'string', length: 64, nullable: true)]
    private(set) ?string $passwordResetToken = null;

    #[ORM\Column(name: 'password_reset_token_expiry', type: 'datetime_immutable', nullable: true)]
    private(set) ?\DateTimeImmutable $passwordResetTokenExpiry = null;

    public function __construct(
        UserId $userId,
        UserEmail $email,
        UserPassword $password,
        UserFirstname $firstname,
        UserLastname $lastname,
        UserLanguagePreference $languagePreference,
        UserConsent $consentGiven,
        \DateTimeImmutable $consentDate,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        array $roles,
        ?UserPasswordResetToken $passwordResetToken = null,
        ?\DateTimeImmutable $passwordResetTokenExpiry = null,

    ) {
        $this->uuid = (string) $userId;
        $this->email = (string) $email;
        $this->password = (string) $password;
        $this->firstname = (string) $firstname;
        $this->lastname = (string) $lastname;
        $this->languagePreference = (string) $languagePreference;
        $this->consentGiven = $consentGiven->toBool();
        $this->consentDate = $consentDate;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->roles = $roles;
        $this->passwordResetToken = $passwordResetToken instanceof UserPasswordResetToken ? (string) $passwordResetToken : null;
        $this->passwordResetTokenExpiry = $passwordResetTokenExpiry;
    }

    public static function fromRepository(array $user): self
    {
        return new self(
            UserId::fromString($user['uuid']),
            UserEmail::fromString($user['email']),
            UserPassword::fromString($user['password']),
            UserFirstname::fromString($user['firstname']),
            UserLastname::fromString($user['lastname']),
            UserLanguagePreference::fromString($user['language_preference']),
            UserConsent::fromBool((bool) $user['consent_given']),
            new \DateTimeImmutable($user['consent_date']),
            new \DateTimeImmutable($user['created_at']),
            new \DateTime($user['updated_at']),
            json_decode($user['roles'], true),
            $user['password_reset_token'] ? UserPasswordResetToken::fromString($user['password_reset_token']) : null,
            $user['password_reset_token_expiry'] ?
                new \DateTimeImmutable($user['password_reset_token_expiry']) :
                null,
        );
    }

    public static function fromUserSignedUpDomainEvent(UserSignedUpDomainEvent $event): self
    {
        return new self(
            UserId::fromString($event->aggregateId),
            UserEmail::fromString($event->email),
            UserPassword::fromString($event->password),
            UserFirstname::fromString($event->firstname),
            UserLastname::fromString($event->lastname),
            UserLanguagePreference::fromString($event->languagePreference),
            UserConsent::fromBool($event->isConsentGiven),
            $event->occurredOn,
            $event->occurredOn,
            \DateTime::createFromImmutable($event->occurredOn),
            $event->roles,
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

    public function getPassword(): string
    {
        return $this->password;
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
            UserSignedUpDomainEvent::class => $this->applyUserSignedUpDomainEvent($event),
            UserFirstnameChangedDomainEvent::class => $this->applyUserFirstnameChangedDomainEvent($event),
            UserLastnameChangedDomainEvent::class => $this->applyUserLastnameChangedDomainEvent($event),
            UserLanguagePreferenceChangedDomainEvent::class => $this->applyUserLanguagePreferenceChangedDomainEvent($event),
            UserPasswordChangedDomainEvent::class => $this->applyUserPasswordChangedDomainEvent($event),
            UserPasswordResetRequestedDomainEvent::class => $this->applyUserPasswordResetRequestedDomainEvent($event),
            UserPasswordResetDomainEvent::class => $this->applyUserPasswordResetDomainEvent($event),
            UserReplayedDomainEvent::class => $this->applyUserReplayedDomainEvent($event),
            UserRewoundDomainEvent::class => $this->applyUserRewoundDomainEvent($event),
            default => throw new \RuntimeException('users.unknownEvent'),
        };
    }

    private function applyUserSignedUpDomainEvent(UserSignedUpDomainEvent $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->email = $event->email;
        $this->password = $event->password;
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->languagePreference = $event->languagePreference;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->createdAt = $event->occurredOn;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = UtcClock::immutableNow();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    private function applyUserFirstnameChangedDomainEvent(UserFirstnameChangedDomainEvent $event): void
    {
        $this->firstname = $event->firstname;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserLastnameChangedDomainEvent(UserLastnameChangedDomainEvent $event): void
    {
        $this->lastname = $event->lastname;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserLanguagePreferenceChangedDomainEvent(
        UserLanguagePreferenceChangedDomainEvent $event,
    ): void {
        $this->languagePreference = $event->languagePreference;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordChangedDomainEvent(UserPasswordChangedDomainEvent $event): void
    {
        $this->password = $event->newPassword;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordResetRequestedDomainEvent(UserPasswordResetRequestedDomainEvent $event): void
    {
        $this->passwordResetToken = $event->passwordResetToken;
        $this->passwordResetTokenExpiry = $event->passwordResetTokenExpiry;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordResetDomainEvent(UserPasswordResetDomainEvent $event): void
    {
        $this->password = $event->password;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserReplayedDomainEvent(UserReplayedDomainEvent $event): void
    {
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->languagePreference = $event->languagePreference;
        $this->email = $event->email;
        $this->password = $event->password;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyUserRewoundDomainEvent(UserRewoundDomainEvent $event): void
    {
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->languagePreference = $event->languagePreference;
        $this->email = $event->email;
        $this->password = $event->password;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
    }
}
