<?php

declare(strict_types=1);

namespace App\UserContext;

use App\SharedContext\UserView;

interface UserViewRepository
{
    public function save(UserView $user): void;

    public function delete(UserView $user): void;

    public function findOneBy(array $criteria): ?UserView;
}
