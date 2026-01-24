<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Adapters;

use App\SharedContext\Domain\Ports\Inbound\Command;
use App\SharedContext\Domain\Ports\Outbound\CommandBus;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CommandBusAdapter implements CommandBus
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    public function execute(Command $command): void
    {
        $this->messageBus->dispatch($command);
    }
}
