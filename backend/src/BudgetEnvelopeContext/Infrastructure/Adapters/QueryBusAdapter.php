<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Adapters;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\QueryInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\QueryBusInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final readonly class QueryBusAdapter implements QueryBusInterface
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    #[\Override]
    public function query(QueryInterface $query): mixed
    {
        return $this->messageBus->dispatch($query)->last(HandledStamp::class)?->getResult();
    }
}
