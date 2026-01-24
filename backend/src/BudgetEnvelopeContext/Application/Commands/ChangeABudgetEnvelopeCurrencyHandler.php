<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;

final readonly class ChangeABudgetEnvelopeCurrencyHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeABudgetEnvelopeCurrency $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        $aggregate->changeCurrency($command->getBudgetEnvelopeCurrency(), $command->getBudgetEnvelopeUserId());
    }
}
