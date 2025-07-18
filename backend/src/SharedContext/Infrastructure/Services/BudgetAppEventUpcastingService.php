<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Services;

use App\Libraries\FluxCapacitor\EventStore\Ports\EventUpcastingServiceInterface;

final class BudgetAppEventUpcastingService implements EventUpcastingServiceInterface
{
    public function upcastEvent(array $eventData): array
    {
        $eventName = $eventData['event_name'];
        $version = $eventData['event_version'] ?? 1;
        
        // Pattern : EventName_v1 → EventName_v2
        return match([$eventName, $version]) {
            // Règles à ajouter au fur et à mesure
            default => $eventData,
        };
    }
    
    private function upcastUserSignedUpV1ToV2(array $eventData): array
    {
        // Exemple pour plus tard
        $payload = json_decode($eventData['payload'], true);
        $payload['phoneNumber'] = $payload['phoneNumber'] ?? '';
        $eventData['payload'] = json_encode($payload);
        $eventData['event_version'] = 2;
        $eventData['event_name'] = 'UserSignedUpDomainEvent_v2';

        return $eventData;
    }
}