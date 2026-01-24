<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Ports;

interface EncryptionService
{
    public function encrypt(string $data, string $userId, bool $isUserSignUpAction = false): array;

    public function decrypt(string $ciphertext, string $iv, string $tag, string $userId): string;
}
