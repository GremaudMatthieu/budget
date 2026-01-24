<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Ports;

interface KeyManagementRepository
{
    public function generateKey(string $userId): string;

    public function getKey(string $userId): ?string;

    public function deleteKey(string $userId): void;
}
