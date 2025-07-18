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
final class EventStore
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\SequenceGenerator(sequenceName: 'event_store_id_seq', allocationSize: 1, initialValue: 1)]
    private int $id {
        get {
            return $this->id;
        }
        set {
            $this->id = $value;
        }
    }

    #[ORM\Column(name: 'stream_id', type: 'string', length: 36)]
    private string $streamId {
        get {
            return $this->streamId;
        }
        set {
            $this->streamId = $value;
        }
    }

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId {
        get {
            return $this->userId;
        }
        set {
            $this->userId = $value;
        }
    }

    #[ORM\Column(name: 'event_name', type: 'string', length: 255)]
    private string $eventName {
        get {
            return $this->eventName;
        }
        set {
            $this->eventName = $value;
        }
    }

    #[ORM\Column(name: 'stream_version', type: 'integer', options: ['default' => 0])]
    public int $streamVersion = 0 {
        get {
            return $this->streamVersion;
        }
        set {
            $this->streamVersion = $value;
        }
    }

    #[ORM\Column(name: 'stream_name', type: 'string', length: 255)]
    public string $streamName {
        get {
            return $this->streamName;
        }
        set {
            $this->streamName = $value;
        }
    }

    #[ORM\Column(name: 'request_id', type: 'string', length: 36)]
    public string $requestId {
        get {
            return $this->requestId;
        }
        set {
            $this->requestId = $value;
        }
    }

    #[ORM\Column(name: 'payload', type: 'json')]
    private array $payload {
        get {
            return $this->payload;
        }
        set {
            $this->payload = $value;
        }
    }

    #[ORM\Column(name: 'meta_data', type: 'json')]
    private array $metaData = [] {
        get {
            return $this->metaData;
        }
        set {
            $this->metaData = $value;
        }
    }

    #[ORM\Column(name:'occurred_on', type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredOn {
        get {
            return $this->occurredOn;
        }
        set {
            $this->occurredOn = $value;
        }
    }

    #[ORM\Column(name: 'event_version', type: 'integer', options: ['default' => 1])]
    public int $eventVersion = 1 {
        get {
            return $this->eventVersion;
        }
        set {
            $this->eventVersion = $value;
        }
    }
}
