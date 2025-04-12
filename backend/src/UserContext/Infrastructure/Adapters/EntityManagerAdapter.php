<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\UserContext\Domain\Ports\Outbound\EntityManagerInterface;
use Doctrine\ORM\EntityManagerInterface as DoctrineEntityManagerInterface;

final readonly class EntityManagerAdapter implements EntityManagerInterface
{
    public function __construct(private DoctrineEntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function remove(object $object): void
    {
        $this->entityManager->remove($object);
    }

    #[\Override]
    public function persist(object $object): void
    {
        $this->entityManager->persist($object);
    }

    #[\Override]
    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
