<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Outbound;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\CommandInterface;

interface CommandBusInterface
{
    public function execute(CommandInterface $command): void;
}
