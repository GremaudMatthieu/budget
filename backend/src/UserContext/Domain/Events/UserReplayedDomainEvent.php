<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Events;

use App\Libraries\FluxCapacitor\Anonymizer\Attributes\PersonalData;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class UserReplayedDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $firstname;
    #[PersonalData]
    public string $lastname;
    #[PersonalData]
    public string $languagePreference;
    #[PersonalData]
    public string $email;
    public bool $isConsentGiven;
    public \DateTime $updatedAt;
    public \DateTimeImmutable $consentDate;
    public string $userId;
    public string $registrationContext;
    public string $providerUserId;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $firstname,
        string $lastname,
        string $languagePreference,
        string $email,
        bool $isConsentGiven,
        string $consentDate,
        string $updatedAt,
        string $userId,
        string $registrationContext,
        string $providerUserId,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->aggregateId = $aggregateId;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->languagePreference = $languagePreference;
        $this->email = $email;
        $this->isConsentGiven = $isConsentGiven;
        $this->updatedAt = new \DateTime($updatedAt);
        $this->consentDate = new \DateTimeImmutable($consentDate);
        $this->userId = $userId;
        $this->registrationContext = $registrationContext;
        $this->providerUserId = $providerUserId;
        $this->requestId = $requestId;
        $this->occurredOn = UtcClock::immutableNow();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'requestId' => $this->requestId,
            'userId' => $this->userId,
            'email' => $this->email,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'languagePreference' => $this->languagePreference,
            'isConsentGiven' => $this->isConsentGiven,
            'consentDate' => $this->consentDate->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'registrationContext' => $this->registrationContext,
            'providerUserId' => $this->providerUserId,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['firstname'],
            $data['lastname'],
            $data['languagePreference'],
            $data['email'],
            $data['isConsentGiven'],
            $data['consentDate'],
            $data['updatedAt'],
            $data['userId'],
            $data['registrationContext'],
            $data['providerUserId'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
