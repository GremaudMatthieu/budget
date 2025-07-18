<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Repositories;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class KeyManagementRepository implements KeyManagementRepositoryInterface
{
    public function __construct(
        private Connection $connection,
        private string $passphrase,
    ) {
    }

    public function generateKey(string $userId): string
    {
        $key = bin2hex(random_bytes(32));
        $encryptedKey = $this->encryptKey($key);

        $this->connection->insert('encryption_keys', [
            'user_id' => $userId,
            'encryption_key' => $encryptedKey,
            'created_at' => new \DateTimeImmutable(
                'now',
                new \DateTimeZone('UTC'),
            )->format(\DateTimeImmutable::ATOM),
        ]);

        return $key;
    }

    public function getKey(string $userId): ?string
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('encryption_key')
            ->from('encryption_keys')
            ->where('user_id = :user_id')
            ->setParameter('user_id', $userId);

        $encryptedKey = $queryBuilder->executeQuery()->fetchOne();

        return !$encryptedKey ? null : $this->decryptKey($encryptedKey);
    }

    public function deleteKey(string $userId): void
    {
        $this->connection->delete('encryption_keys', ['user_id' => $userId]);
    }

    private function encryptKey(string $key): string
    {
        $iv = random_bytes(16);

        $encryptedKey = openssl_encrypt(
            $key,
            'aes-256-gcm',
            $this->passphrase,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return base64_encode($iv.$tag.$encryptedKey);
    }

    public function decryptKey(string $encryptedKey): string
    {
        $decoded = base64_decode($encryptedKey, true);

        $iv = substr($decoded, 0, 16);
        $tag = substr($decoded, 16, 16);
        $ciphertext = substr($decoded, 32);

        return openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->passphrase,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
}
