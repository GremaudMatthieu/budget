<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope;

interface BudgetEnvelopeLedgerEntryFinder
{
    public function save(BudgetEnvelopeLedgerEntry $budgetEnvelopeLedgerEntryView): void;

    public function delete(string $budgetEnvelopeUuid): void;
}
