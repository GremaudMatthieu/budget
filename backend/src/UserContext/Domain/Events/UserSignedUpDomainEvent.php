<?php

namespace App\UserContext\Domain\Events;

use App\Libraries\FluxCapacitor\Anonymizer\Attributes\PersonalData;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\AbstractUserSignedUpDomainEventInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class UserSignedUpDomainEvent implements UserDomainEventInterface, AbstractUserSignedUpDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $email;
    #[PersonalData]
    public string $firstname;
    #[PersonalData]
    public string $lastname;
    #[PersonalData]
    public string $languagePreference;
    public bool $isConsentGiven;
    public array $roles;
    public string $userId;
    public string $requestId;
    public string $registrationContext;
    public string $providerUserId;
    public string $contextId;
    public string $context;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $email,
        string $firstname,
        string $lastname,
        string $languagePreference,
        bool $isConsentGiven,
        array $roles,
        string $userId,
        string $registrationContext,
        string $providerUserId,
        string $contextId,
        string $context,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID
    ) {
        $this->aggregateId = $aggregateId;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->languagePreference = $languagePreference;
        $this->isConsentGiven = $isConsentGiven;
        $this->roles = $roles;
        $this->userId = $userId;
        $this->requestId = $requestId;
        $this->registrationContext = $registrationContext;
        $this->providerUserId = $providerUserId;
        $this->contextId = $contextId;
        $this->context = $context;
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
            'roles' => $this->roles,
            'registrationContext' => $this->registrationContext,
            'providerUserId' => $this->providerUserId,
            'contextId' => $this->contextId,
            'context' => $this->context,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['email'],
            $data['firstname'],
            $data['lastname'],
            $data['languagePreference'],
            $data['isConsentGiven'],
            $data['roles'],
            $data['userId'],
            $data['registrationContext'],
            $data['providerUserId'],
            $data['contextId'],
            $data['context'],
            $data['requestId']
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
