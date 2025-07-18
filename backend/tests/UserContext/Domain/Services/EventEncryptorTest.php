<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Services;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\EncryptionServiceInterface;
use App\Libraries\FluxCapacitor\Anonymizer\Services\EventEncryptor;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent_v1;
use PHPUnit\Framework\TestCase;

final class EventEncryptorTest extends TestCase
{
    private EncryptionServiceInterface $encryptionService;
    private EventEncryptor $eventEncryptor;

    protected function setUp(): void
    {
        $this->encryptionService = $this->createMock(EncryptionServiceInterface::class);
        $this->eventEncryptor = new EventEncryptor($this->encryptionService);
    }

    public function testEncrypt(): void
    {
        $userId = 'user123';
        $event = new UserFirstnameChangedDomainEvent_v1('aggregateId', 'sensitive data', 'aggregateId');

        $this->encryptionService
            ->method('encrypt')
            ->with('sensitive data', $userId, false)
            ->willReturn([
                'ciphertext' => 'encryptedData',
                'iv' => 'iv',
                'tag' => 'tag',
            ]);

        $encryptedEvent = $this->eventEncryptor->encrypt($event, $userId);

        $this->assertSame(
            json_encode(['ciphertext' => 'encryptedData', 'iv' => 'iv', 'tag' => 'tag']),
            $encryptedEvent->firstname
        );
    }

    public function testDecrypt(): void
    {
        $userId = 'user123';
        $event = new UserFirstnameChangedDomainEvent_v1('aggregateId', json_encode([
            'ciphertext' => 'encryptedData',
            'iv' => 'iv',
            'tag' => 'tag',
        ]), 'aggregateId');

        $this->encryptionService
            ->method('decrypt')
            ->with('encryptedData', 'iv', 'tag', $userId)
            ->willReturn('sensitive data');

        $decryptedEvent = $this->eventEncryptor->decrypt($event, $userId);

        $this->assertSame('sensitive data', $decryptedEvent->firstname);
    }
}
