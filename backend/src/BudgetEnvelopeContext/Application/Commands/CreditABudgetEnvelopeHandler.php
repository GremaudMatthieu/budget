<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepository;

final readonly class CreditABudgetEnvelopeHandler
{
    public function __construct(private EventSourcedRepository $eventSourcedRepository)
    {
    }

    public function __invoke(CreditABudgetEnvelope $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        $aggregate->credit(
            $command->getBudgetEnvelopeCreditMoney(),
            $command->getBudgetEnvelopeEntryDescription(),
            $command->getBudgetEnvelopeUserId(),
        );
    }
}
