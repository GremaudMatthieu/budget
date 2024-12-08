<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Repository;

use App\UserManagement\Domain\Model\UserInterface;
use App\UserManagement\Domain\Repository\UserCommandRepositoryInterface;
use App\UserManagement\Infrastructure\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserCommandRepository extends ServiceEntityRepository implements UserCommandRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct($registry, User::class);
    }

    public function save(UserInterface $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    public function delete(UserInterface $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }
}