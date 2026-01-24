<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope;

interface BudgetEnvelopeFinder
{
    public function save(BudgetEnvelope $budgetEnvelope): void;

    public function delete(BudgetEnvelope $budgetEnvelope): void;

    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetEnvelope;

    public function findOneEnvelopeWithItsLedgerBy(array $criteria, ?array $orderBy = null): array;

    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): BudgetEnvelopesPaginated;
}
