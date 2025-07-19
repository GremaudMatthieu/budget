<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_store')]
#[ORM\UniqueConstraint(name: 'unique_stream_version', columns: ['stream_id', 'stream_version'])]
#[ORM\Index(name: 'idx_stream_id', columns: ['stream_id'])]
#[ORM\Index(name: 'idx_stream_name', columns: ['stream_name'])]
#[ORM\Index(name: 'idx_event_name', columns: ['event_name'])]
#[ORM\Index(name: 'idx_event_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_occurred_on', columns: ['occurred_on'])]
#[ORM\Index(name: 'idx_event_version', columns: ['event_version'])]
class EventStore
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'event_store_id_seq', allocationSize: 1, initialValue: 1)]
    public int $id;

    #[ORM\Column(name: 'stream_id', type: 'string', length: 36)]
    public string $streamId;

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    public string $userId;

    #[ORM\Column(name: 'event_name', type: 'string', length: 255)]
    public string $eventName;

    #[ORM\Column(name: 'stream_version', type: 'integer', options: ['default' => 0])]
    public int $streamVersion = 0;

    #[ORM\Column(name: 'stream_name', type: 'string', length: 255)]
    public string $streamName;

    #[ORM\Column(name: 'request_id', type: 'string', length: 36)]
    public string $requestId;

    #[ORM\Column(name: 'payload', type: 'json')]
    public array $payload;

    #[ORM\Column(name: 'meta_data', type: 'json')]
    public array $metaData = [];

    #[ORM\Column(name:'occurred_on', type: 'datetime_immutable')]
    public \DateTimeImmutable $occurredOn;

    #[ORM\Column(name: 'event_version', type: 'integer', options: ['default' => 1])]
    public int $eventVersion = 1;
}
