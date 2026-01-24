<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

use App\SharedContext\Domain\Ports\Inbound\Command;

interface CommandBus
{
    public function execute(Command $command): void;
}
