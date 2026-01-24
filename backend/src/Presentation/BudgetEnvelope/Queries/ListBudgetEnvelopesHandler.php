<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\Queries;

use App\Presentation\BudgetEnvelope\BudgetEnvelopesPaginated;
use App\Presentation\BudgetEnvelope\BudgetEnvelopeFinder;

final readonly class ListBudgetEnvelopesHandler
{
    public function __construct(
        private BudgetEnvelopeFinder $budgetEnvelopesRepository,
    ) {
    }

    public function __invoke(ListBudgetEnvelopes $query): BudgetEnvelopesPaginated
    {
        return $this->budgetEnvelopesRepository->findBy(
            [
                'user_uuid' => (string) $query->getBudgetEnvelopeUserId(),
                'is_deleted' => false,
            ],
            $query->getOrderBy(),
            $query->getLimit(),
            $query->getOffset(),
        );
    }
}
