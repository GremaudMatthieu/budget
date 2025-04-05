<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeContext\Application\Queries\ListBudgetEnvelopesQuery;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopesPaginatedInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;

final readonly class ListBudgetEnvelopesQueryHandler
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopesRepository,
    ) {
    }

    public function __invoke(ListBudgetEnvelopesQuery $query): BudgetEnvelopesPaginatedInterface
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
