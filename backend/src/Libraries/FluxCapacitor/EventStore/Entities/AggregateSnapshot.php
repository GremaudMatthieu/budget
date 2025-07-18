<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'aggregate_snapshots')]
#[ORM\Index(name: 'idx_aggregate_snapshots_id_type', columns: ['aggregate_id', 'aggregate_type'])]
#[ORM\UniqueConstraint(name: 'unique_aggregate_version', columns: ['aggregate_id', 'version'])]
class AggregateSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\Column(type: 'string', length: 36)]
    public string $aggregateId;

    #[ORM\Column(type: 'string', length: 255)]
    public string $aggregateType;

    #[ORM\Column(type: 'integer')]
    public int $version;

    #[ORM\Column(type: 'text')]
    public string $data;

    #[ORM\Column(type: 'datetime_immutable')]
    public \DateTimeImmutable $createdAt;

    public function __construct(
        string $aggregateId,
        string $aggregateType,
        int $version,
        string $data,
    ) {
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
        $this->version = $version;
        $this->data = $data;
        $this->createdAt = new \DateTimeImmutable();
    }
}
