<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class ChangeABudgetEnvelopeTargetedAmountCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeABudgetEnvelopeTargetedAmountCommand $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        $aggregate->updateTargetedAmount(
            $command->getBudgetEnvelopeTargetedAmount(),
            $command->getBudgetEnvelopeUserId(),
        );
    }
}
