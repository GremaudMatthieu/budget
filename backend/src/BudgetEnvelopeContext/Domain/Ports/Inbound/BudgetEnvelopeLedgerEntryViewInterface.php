<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;

interface BudgetEnvelopeLedgerEntryViewInterface
{
    public static function fromRepository(array $budgetEnvelopeLedgerEntry): self;

    public function jsonSerialize(): array;

    public static function fromBudgetEnvelopeCreditedDomainEvent(
        BudgetEnvelopeCreditedDomainEvent $budgetEnvelopeCreditedDomainEvent,
        string $userUuid,
    ): self;

    public static function fromBudgetEnvelopeDebitedDomainEvent(
        BudgetEnvelopeDebitedDomainEvent $budgetEnvelopeDebitedDomainEvent,
        string $userUuid,
    ): self;
}
