<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Adapters;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\CommandInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\CommandBusInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CommandBusAdapter implements CommandBusInterface
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    public function execute(CommandInterface $command): void
    {
        $this->messageBus->dispatch($command);
    }
}
