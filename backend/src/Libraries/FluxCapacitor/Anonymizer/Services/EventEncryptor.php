<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Services;

use App\Libraries\FluxCapacitor\Anonymizer\Attributes\PersonalData;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\AbstractUserDomainEventInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\AbstractUserSignedUpDomainEventInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EncryptionServiceInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EventEncryptorInterface;

final readonly class EventEncryptor implements EventEncryptorInterface
{
    public function __construct(private EncryptionServiceInterface $encryptionService)
    {
    }

    public function encrypt(AbstractUserDomainEventInterface $event, string $userId): AbstractUserDomainEventInterface
    {
        $reflection = new \ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasPersonalDataAttribute($property)) {
                $property->setAccessible(true);
                $value = $property->getValue($event);

                if (!is_null($value)) {
                    $encrypted = $this->encryptionService->encrypt(
                        (string) $value,
                        $userId,
                        $event instanceof AbstractUserSignedUpDomainEventInterface,
                    );
                    $property->setValue($event, json_encode($encrypted));
                }
            }
        }

        return $event;
    }

    public function decrypt(AbstractUserDomainEventInterface $event, string $userId): AbstractUserDomainEventInterface
    {
        $reflection = new \ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasPersonalDataAttribute($property)) {
                $property->setAccessible(true);
                $value = $property->getValue($event);

                if (!is_null($value)) {
                    $encrypted = json_decode($value, true);
                    $decrypted = $this->encryptionService->decrypt(
                        $encrypted['ciphertext'],
                        $encrypted['iv'],
                        $encrypted['tag'],
                        $userId,
                    );
                    $property->setValue($event, $decrypted);
                }
            }
        }

        return $event;
    }

    private function hasPersonalDataAttribute(\ReflectionProperty $property): bool
    {
        return count($property->getAttributes(PersonalData::class)) > 0;
    }
}
