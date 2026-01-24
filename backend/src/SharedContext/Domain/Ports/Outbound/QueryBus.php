<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

use App\SharedContext\Domain\Ports\Inbound\Query;

interface QueryBus
{
    public function query(Query $query): mixed;
}
