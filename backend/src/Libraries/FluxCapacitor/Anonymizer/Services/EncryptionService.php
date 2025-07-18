<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Services;

use App\Libraries\FluxCapacitor\Anonymizer\Exceptions\UserEncryptionException;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\EncryptionServiceInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\KeyManagementRepositoryInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Traits\EncryptedKeyCacheTrait;

final class EncryptionService implements EncryptionServiceInterface
{
    use EncryptedKeyCacheTrait;

    private const string CIPHER = 'aes-256-gcm';
    private const int IV_SIZE = 12;
    private const int TAG_SIZE = 16;

    public function __construct(private readonly KeyManagementRepositoryInterface $keyManagementRepository)
    {
    }

    public function encrypt(string $data, string $userId, ?bool $isUserSignUpAction = false): array
    {
        $iv = random_bytes(self::IV_SIZE);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $data,
            self::CIPHER,
            $this->getKeyForUser($userId, $isUserSignUpAction),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_SIZE,
        );


        if (false === $ciphertext) {
            throw UserEncryptionException::fromEncryptFailure();
        }

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
        ];
    }

    public function decrypt(string $ciphertext, string $iv, string $tag, string $userId): string
    {
        $plaintext = openssl_decrypt(
            base64_decode($ciphertext, true),
            self::CIPHER,
            $this->getKeyForUser($userId),
            OPENSSL_RAW_DATA,
            base64_decode($iv, true),
            base64_decode($tag, true),
        );

        if (false === $plaintext) {
            throw UserEncryptionException::fromDecryptFailure();
        }

        return $plaintext;
    }

    private function getKeyForUser(string $userId, bool $isUserSignUpAction = false): string
    {
        if ($this->getKeyByUserId($userId)) {
            return $this->getKeyByUserId($userId);
        }

        $key = $this->keyManagementRepository->getKey($userId);

        if ($isUserSignUpAction && null === $key) {
            $this->storeKeyByUserId($userId, $this->keyManagementRepository->generateKey($userId));

            return $this->getKeyByUserId($userId);
        }

        if (null === $key) {
            throw UserEncryptionException::fromGetKeyFailure();
        }

        $this->storeKeyByUserId($userId, $key);

        return $key;
    }
}
