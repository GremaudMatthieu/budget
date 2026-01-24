<?php

declare(strict_types=1);

namespace App\Presentation\BudgetEnvelope\Infrastructure\Persistence\Repositories;

use App\Presentation\BudgetEnvelope\BudgetEnvelopeLedgerEntry;
use App\Presentation\BudgetEnvelope\BudgetEnvelopeLedgerEntryFinder;
use Doctrine\DBAL\Connection;

final class BudgetEnvelopeLedgerEntryViewRepository implements BudgetEnvelopeLedgerEntryFinder
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function save(BudgetEnvelopeLedgerEntry $budgetEnvelopeLedgerEntryView): void
    {
        $this->connection->executeStatement('
    INSERT INTO budget_envelope_ledger_entry_view (budget_envelope_uuid, created_at, monetary_amount, entry_type, description, user_uuid)
    VALUES (:budget_envelope_uuid, :created_at, :monetary_amount, :entry_type, :description, :user_uuid)
', [
            'budget_envelope_uuid' => $budgetEnvelopeLedgerEntryView->budgetEnvelopeUuid,
            'created_at' => $budgetEnvelopeLedgerEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'monetary_amount' => $budgetEnvelopeLedgerEntryView->monetaryAmount,
            'entry_type' => $budgetEnvelopeLedgerEntryView->entryType,
            'description' => $budgetEnvelopeLedgerEntryView->description,
            'user_uuid' => $budgetEnvelopeLedgerEntryView->userUuid,
        ]);
    }

    #[\Override]
    public function delete(string $budgetEnvelopeUuid): void
    {
        $this->connection->delete('budget_envelope_ledger_entry_view', ['budget_envelope_uuid' => $budgetEnvelopeUuid]);
    }
}
