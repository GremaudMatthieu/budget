<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

interface UserViewRepositoryInterface
{
    public function save(UserViewInterface $user): void;

    public function delete(UserViewInterface $user): void;

    public function findOneBy(array $criteria): ?UserViewInterface;
}
