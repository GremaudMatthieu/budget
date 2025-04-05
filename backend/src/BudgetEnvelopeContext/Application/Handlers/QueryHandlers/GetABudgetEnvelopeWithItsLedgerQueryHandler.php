<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeContext\Application\Queries\GetABudgetEnvelopeWithItsLedgerQuery;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;

final readonly class GetABudgetEnvelopeWithItsLedgerQueryHandler
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    /**
     * @throws BudgetEnvelopeNotFoundException
     */
    public function __invoke(GetABudgetEnvelopeWithItsLedgerQuery $query): array
    {
        $budgetEnvelope = $this->budgetEnvelopeViewRepository->findOneEnvelopeWithItsLedgerBy([
            'uuid' => (string) $query->getBudgetEnvelopeId(),
            'user_uuid' => (string) $query->getBudgetEnvelopeUserId(),
            'is_deleted' => false,
        ]);

        if ([] === $budgetEnvelope) {
            throw new BudgetEnvelopeNotFoundException();
        }

        return $budgetEnvelope;
    }
}
