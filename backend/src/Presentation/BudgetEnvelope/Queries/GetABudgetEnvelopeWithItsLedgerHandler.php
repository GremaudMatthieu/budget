<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\Queries;

use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFound;
use App\Presentation\BudgetEnvelope\BudgetEnvelopeFinder;

final readonly class GetABudgetEnvelopeWithItsLedgerHandler
{
    public function __construct(
        private BudgetEnvelopeFinder $budgetEnvelopeViewRepository,
    ) {
    }

    /**
     * @throws BudgetEnvelopeNotFound
     */
    public function __invoke(GetABudgetEnvelopeWithItsLedger $query): array
    {
        $budgetEnvelope = $this->budgetEnvelopeViewRepository->findOneEnvelopeWithItsLedgerBy([
            'uuid' => (string) $query->getBudgetEnvelopeId(),
            'user_uuid' => (string) $query->getBudgetEnvelopeUserId(),
            'is_deleted' => false,
        ]);

        if ([] === $budgetEnvelope) {
            throw new BudgetEnvelopeNotFound();
        }

        return $budgetEnvelope;
    }
}
