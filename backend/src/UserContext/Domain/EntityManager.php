<?php

declare(strict_types=1);

namespace App\UserContext\Domain;

interface EntityManager
{
    public function remove(object $object): void;

    public function persist(object $object): void;

    public function flush(): void;
}
