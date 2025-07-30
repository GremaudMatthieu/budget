<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Anonymizer\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'encryption_keys')]
#[ORM\Index(name: 'idx_encryption_keys_user_id', columns: ['user_id'])]
class EncryptionKey
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    public int $id;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    public string $userId;

    #[ORM\Column(name: 'encryption_key', type: 'text')]
    public string $encryptionKey;

    #[ORM\Column(name:'created_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;
}
