<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Outbound;

interface EntityManagerInterface
{
    public function remove(object $object): void;

    public function persist(object $object): void;

    public function flush(): void;
}
